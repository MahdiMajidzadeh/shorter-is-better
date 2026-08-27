# Docker & Dokploy deployment

The app ships as a single self-contained image built on
[FrankenPHP](https://frankenphp.dev) — PHP 8.4 and a Caddy web server in one
process, no nginx, no php-fpm. Inside the container, **supervisord is PID 1**
and runs the web server plus the queue worker, so production is exactly one
Dokploy Application running one container.

| File | Purpose |
| --- | --- |
| `Dockerfile` | Production image (`base` → `vendor` → `assets` → `runtime` stages) |
| `docker/Caddyfile` | Web server config; listens on `:8080`, TLS handled upstream |
| `docker/php.ini` | Production PHP + OPcache settings |
| `docker/supervisord.conf` | Process model: `web` + `worker` programs |
| `docker/entrypoint.sh` | Boot sequence: wait for DB → migrate → warm caches → supervisord |
| `compose.local.yaml` | Local development only — builds from source, self-hosts MariaDB/Redis |
| `.github/workflows/release.yml` | Builds + pushes to GHCR, then pings Dokploy |

There is **no production compose file**: Dokploy pulls the published image and
runs it directly (Docker-image provider), and all configuration lives in the
Application's Environment tab. No `.env` file exists anywhere in production —
not committed, not in the image, not required at build time.

## Image layout

- Serves `/app/public` on port **8080** as the unprivileged `www-data` user.
  Application code is root-owned and read-only at runtime; only `storage/` and
  `bootstrap/cache` are writable.
- Configuration comes from **real environment variables** injected by the
  Dokploy panel. Laravel's config/route/view/event caches are built by the
  entrypoint at container start, never at build time, so the image cannot
  freeze build-time values — env changes take effect on redeploy.
- `HEALTHCHECK` hits `GET /up`, which deliberately touches neither the database
  nor the cache — a datastore blip must not trigger a restart loop.
- `RUN_MIGRATIONS=false` disables migrations on boot if you'd rather run them
  by hand.
- `bash` is installed (Alpine ships only busybox ash) so `docker exec`
  sessions and one-off scripts behave as expected.

Frontend assets are built **inside the image** by the `assets` stage: Tailwind
CSS 4 compiles `resources/css/app.css` into `public/index.css`. The stage needs
`vendor/` present first because the stylesheet `@import`s and `@source`s Flux
UI files from `vendor/livewire/flux` — that is why it copies vendor from the
composer stage. The `public/index.css` committed to the repo exists only for
non-Docker local development (`php artisan serve`); rebuild it with
`npm run build` when views or `resources/css/app.css` change.

## Process model

One image, **one container**, supervised programs — the repo deploys as a
single Dokploy Application, so the background processes live inside the
container under supervisord rather than as extra containers. (A previous
compose-based, role-per-container layout was never adopted in the panel, which
is why queue workers silently stopped running: production ran only the single
Application container. This layout makes the deployed shape and the repo agree.)

- **`web`** — `frankenphp run` (Caddy + PHP, the only process the healthcheck
  probes).
- **`worker`** — required: [Telegraph](https://defstudio.github.io/telegraph)
  sends every bot API call through a queued job (`SendRequestToTelegramJob`),
  and production runs `QUEUE_CONNECTION=redis`. Flags:
  `--tries=3 --max-time=3600 --sleep=3`. Scale with `numprocs` in
  `docker/supervisord.conf`, never with a second container.
- **no `scheduler`** — `App\Console\Kernel::schedule()` is empty (has been
  since Telescope was removed). If you add a scheduled task, add this program
  to `docker/supervisord.conf`:

  ```ini
  [program:scheduler]
  command=php artisan schedule:work
  autorestart=true
  priority=30
  stdout_logfile=/dev/stdout
  stdout_logfile_maxbytes=0
  stderr_logfile=/dev/stderr
  stderr_logfile_maxbytes=0
  ```

Known tradeoff, accepted deliberately: the container healthcheck probes only
the web server, so a crash-looping worker can hide behind a green tick.
Mitigations: every program has `autorestart=true` and logs to stdout/stderr
(`docker logs` interleaves all of them), and worker liveness is one command:

```bash
docker exec <container> supervisorctl status
```

Every program must show `RUNNING`. If the queue ever grows real backlogs,
schedule `php artisan queue:monitor` and alert on it.

## Environment variables (the Dokploy panel is the single source of truth)

Every key below is set in the Application's *Environment* tab. A key missing
from the tab is a key the container does not have.

| Key | Value / notes |
| --- | --- |
| `APP_NAME` | `Shorter` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | **Required.** Generate once: `docker run --rm <image> php artisan key:generate --show`. Changing it invalidates every session and encrypted value — the container refuses to boot without one rather than generating a throwaway. |
| `APP_URL` | **Required.** Public URL including scheme; short links are built from this. |
| `LOG_CHANNEL` | `stderr` (so `docker logs` gets everything) |
| `LOG_LEVEL` | `warning` |
| `ADMIN_PASSWORD` | Password for the seeded `admin` account; empty = seeder generates one and prints it once. |
| `DB_CONNECTION` | `mariadb` |
| `DB_HOST` | Internal hostname of the **panel-managed database** on `dokploy-network` (from the database service's page in Dokploy). |
| `DB_PORT` | `3306` |
| `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` | As created in the panel database. |
| `REDIS_HOST` | Internal hostname of the panel-managed Redis. |
| `REDIS_PORT` | `6379` |
| `REDIS_PASSWORD` | As created in the panel Redis (`null` if none). |
| `CACHE_DRIVER` | `redis` |
| `SESSION_DRIVER` | `redis` |
| `QUEUE_CONNECTION` | `redis` — the supervised worker processes this queue. |
| `SESSION_SECURE_COOKIE` | `true` (TLS is terminated by Dokploy's Traefik; the cookie must still be marked secure). |
| `SENTRY_LARAVEL_DSN` | Optional error reporting. |
| `SENTRY_TRACES_SAMPLE_RATE` | Optional. |
| `RUN_MIGRATIONS` | Optional, default `true`. |
| `DB_WAIT_TIMEOUT` | Optional, default `60` (seconds the entrypoint waits for the DB). |

## One-time Dokploy setup / migration from the compose stack

The Application used to be a Compose stack self-hosting MariaDB and Redis.
Converting it (do these in order — **data moves are manual, and nothing here
deletes the old volumes**):

1. **Create the panel datastores.** In Dokploy: *Create Database* → MariaDB,
   and a Redis. Note their internal hostnames on `dokploy-network`.
2. **Move the data** (MariaDB — the `mariadb-data` volume holds real
   production data):
   ```bash
   # On the host, dump from the old compose stack's DB container:
   docker exec <old-mariadb-container> \
     mariadb-dump -u root -p"$DB_ROOT_PASSWORD" --databases shorter > shorter.sql
   # Restore into the panel-managed database:
   docker exec -i <panel-mariadb-container> \
     mariadb -u root -p"<panel-root-password>" < shorter.sql
   ```
   Redis holds cache, sessions and the queue backlog — let the worker drain the
   queue (`redis-cli llen queues:default` = 0) before switching; sessions and
   cache rebuild themselves (users get logged out once).
3. **Convert the Application.** Change it to (or recreate it as) a
   **Docker-image provider** Application pointing at
   `ghcr.io/mahdimajidzadeh/shorter-is-better:latest`, attached to
   `dokploy-network`.
4. **Environment tab:** every key from the table above, with `DB_HOST` /
   `REDIS_HOST` pointing at the panel datastores. (`DB_ROOT_PASSWORD` is no
   longer needed — that belonged to the compose-hosted MariaDB. `APP_IMAGE` /
   `IMAGE_TAG` are gone too — the image is chosen in the provider settings.)
5. **Mount:** a volume at `/app/storage`. It holds `storage/settings.json`
   (anlutro/l4-settings: homepage copy and channel settings) and uploads —
   copy the contents of the old `app-storage` volume into it.
6. **Port / domain:** route the domain to container port **8080**. Traefik
   terminates TLS; the container trusts its `X-Forwarded-*` headers (see
   `app/Http/Middleware/TrustProxies.php`).
7. **Stop timeout:** raise the container stop timeout to at least **90s** (the
   worker's `stopwaitsecs`), or docker SIGKILLs supervisord ~10s into a
   redeploy and a running job dies mid-flight.
8. **Webhook:** copy the Application's Webhook URL (*Deployments* tab) into the
   GitHub Actions secret — it is a live deploy trigger, never inline it in the
   public workflow file:
   ```bash
   gh secret set DOKPLOY_WEBHOOK_URL --repo MahdiMajidzadeh/shorter-is-better
   ```
9. **Verify** the app works against the panel datastores (sign in, create a
   link, check `supervisorctl status`), and only then stop the old compose
   stack. Keep the old volumes until the new setup has survived a few days.

## Release flow

| Trigger | Image tags published | Dokploy |
| --- | --- | --- |
| Push tag `v1.2.3` / publish a release | `latest`, `1.2.3`, `1.2`, `sha-<commit>` | Redeployed via webhook |
| `workflow_dispatch` with an existing tag | same, for that tag | Redeployed via webhook |
| Push to `main` | none — the workflow does not run | Untouched |
| Pull request | none (build only) | Untouched |

A digit must follow the `v` — the tag filter rejects typos like `v.0.2.5`,
which once shipped an image under that name. Releasing is one command:

```bash
git tag v1.2.3 && git push origin v1.2.3
```

or, because bare tag pushes have not always produced push events reliably:

```bash
gh release create v1.2.3 --generate-notes
```

The `deploy` job **fails loudly if the webhook secret is missing or Dokploy
refuses the call** — a silently-skipped deploy must never hide behind a green
tick. (A 404 usually means the webhook token was regenerated — update the
secret.)

**Rollback:** point the Application's image at a pinned tag (e.g. `…:1.2.2`)
in Dokploy and redeploy.

**Build speed** is a feature: the workflow keeps a `:buildcache` ref on GHCR
(shared across tag refs, unlike GitHub's per-ref action cache), so a release
where only app code changed reuses the extension/composer/npm layers and takes
minutes, not a cold fifteen. Keep expensive layers keyed on what they depend on
and nothing more — `COPY . .` stays below all of them in the Dockerfile.
A possible future improvement (not done yet): a shared prebuilt base image
(`ghcr.io/mahdimajidzadeh/php-base:8.4` = FrankenPHP + bash + the union of the
four repos' extensions), rebuilt weekly by its own tiny repo, would let all
four repos skip extension compilation entirely even on a cold cache.

## Run it locally

Local development is the only place a database container and a local env file
are allowed:

```bash
cat > .env.docker <<'EOF'
APP_KEY=        # php artisan key:generate --show
DB_PASSWORD=secret
DB_ROOT_PASSWORD=secret
EOF
docker compose -f compose.local.yaml --env-file .env.docker up --build
```

The stack comes up on <http://localhost:8080>, running the same
supervisord process model as production (web + worker in one container).

## Operating notes

- **Persistent state.** `/app/storage` holds `storage/settings.json` — losing
  that mount loses the homepage copy and channel settings. Back it up.
- **Worker liveness:** `docker exec <container> supervisorctl status`.
- **Artisan in the running container:**
  ```bash
  docker exec <container> php artisan migrate:status
  ```
- **Architecture.** The workflow builds `linux/amd64` only. If you deploy to an
  arm64 host, add `linux/arm64` to `platforms` in the workflow — expect a much
  slower build, since the PHP extensions are compiled under emulation.
