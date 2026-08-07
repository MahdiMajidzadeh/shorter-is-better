#!/bin/sh
set -e

# CONTAINER_ROLE decides how much bootstrapping this container does:
#   app       - waits for the DB, migrates, warms caches, then serves HTTP
#   worker    - waits for the DB, then processes the queue
#   scheduler - waits for the DB, then runs the scheduler
# Only the `app` role touches the database schema, so scaling workers never
# races on migrations.
ROLE="${CONTAINER_ROLE:-app}"

log() { echo "[entrypoint] $*"; }

# ---------------------------------------------------------------------------
# Writable paths. A named volume mounted at /app/storage starts out as a copy
# of the image, but a bind mount starts out empty — recreate what Laravel needs.
# ---------------------------------------------------------------------------
mkdir -p \
	storage/app/public \
	storage/framework/cache/data \
	storage/framework/sessions \
	storage/framework/views \
	storage/logs \
	bootstrap/cache

# ---------------------------------------------------------------------------
# APP_KEY is required. Generating one on boot would silently invalidate every
# session and every encrypted value on each restart, so fail loudly instead.
# ---------------------------------------------------------------------------
if [ -z "${APP_KEY}" ]; then
	log "FATAL: APP_KEY is not set."
	log "Generate one with:  docker run --rm <image> php artisan key:generate --show"
	log "then set it as an environment variable for this service."
	exit 1
fi

# ---------------------------------------------------------------------------
# Wait for the database. Dokploy starts containers in parallel and the database
# is usually the slower of the two.
#
# The PDO driver name is not always Laravel's connection name: MariaDB is a
# first-class connection in Laravel but PDO only knows `mysql:`. Building the
# DSN from DB_CONNECTION verbatim would make every probe throw "could not find
# driver", and the container would exit after the full timeout with a message
# blaming the network.
# ---------------------------------------------------------------------------
pdo_driver() {
	case "${DB_CONNECTION:-mysql}" in
		mariadb) echo "mysql" ;;
		*) echo "${DB_CONNECTION:-mysql}" ;;
	esac
}

wait_for_database() {
	timeout="${DB_WAIT_TIMEOUT:-60}"
	elapsed=0
	driver="$(pdo_driver)"

	while [ "${elapsed}" -lt "${timeout}" ]; do
		if PDO_DRIVER="${driver}" php -r '
			$dsn = sprintf(
				"%s:host=%s;port=%s",
				getenv("PDO_DRIVER"),
				getenv("DB_HOST") ?: "127.0.0.1",
				getenv("DB_PORT") ?: "3306"
			);
			try {
				new PDO($dsn, getenv("DB_USERNAME") ?: "", getenv("DB_PASSWORD") ?: "");
				exit(0);
			} catch (Throwable $e) {
				exit(1);
			}
		' 2>/dev/null; then
			log "database is reachable"
			return 0
		fi

		elapsed=$((elapsed + 2))
		sleep 2
	done

	log "FATAL: database not reachable after ${timeout}s (DB_HOST=${DB_HOST:-127.0.0.1} DB_PORT=${DB_PORT:-3306} DB_USERNAME=${DB_USERNAME:-})"
	exit 1
}

case "${DB_CONNECTION:-mysql}" in
	sqlite) log "sqlite connection, skipping database wait" ;;
	*) wait_for_database ;;
esac

# ---------------------------------------------------------------------------
# Laravel bootstrapping.
#
# Caches live in bootstrap/ and storage/, both of which survive a container
# restart — so drop them first. Otherwise a changed environment variable would
# be masked by the config cache written on the previous boot, including for the
# migration below.
# ---------------------------------------------------------------------------
php artisan config:clear --no-interaction
php artisan package:discover --ansi --no-interaction

if [ "${ROLE}" = "app" ] && [ "${AUTO_MIGRATE:-true}" = "true" ]; then
	log "running migrations"
	php artisan migrate --force --no-interaction
elif [ "${ROLE}" = "app" ]; then
	log "AUTO_MIGRATE is not 'true', skipping migrations"
fi

php artisan config:cache --no-interaction
php artisan event:cache --no-interaction

if [ "${ROLE}" = "app" ]; then
	php artisan route:cache --no-interaction
	# Compiled views land in the shared storage volume; only the HTTP role
	# needs them, and warming from a single container avoids a write race.
	php artisan view:cache --no-interaction
fi

log "role=${ROLE} ready, starting: $*"
exec "$@"
