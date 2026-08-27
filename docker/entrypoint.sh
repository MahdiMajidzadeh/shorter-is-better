#!/bin/sh
set -e

# Runs exactly once per container boot, before supervisord starts any program:
# wait for the DB, migrate, build the Laravel caches from the RUNNING
# environment (injected by the Dokploy panel) — never from build time, so the
# image cannot freeze stale env values.

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
	log "then set it in the Dokploy Environment tab."
	exit 1
fi

# ---------------------------------------------------------------------------
# Wait for the database. Dokploy starts containers in parallel (the panel
# database is its own service) and the database is usually the slower to come
# up, e.g. after a host reboot.
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

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
	log "running migrations"
	php artisan migrate --force --no-interaction
else
	log "RUN_MIGRATIONS is not 'true', skipping migrations"
fi

# One container is the single writer of these caches, so there is no write
# race to avoid — warm everything before any program starts.
php artisan config:cache --no-interaction
php artisan event:cache --no-interaction
php artisan route:cache --no-interaction
php artisan view:cache --no-interaction

log "ready, starting: $*"
exec "$@"
