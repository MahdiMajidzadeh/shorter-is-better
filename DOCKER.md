# Docker & Dokploy deployment

The app ships as a single self-contained image built on
[FrankenPHP](https://frankenphp.dev) — PHP 8.4 and a Caddy web server in one
process, so there is no nginx, php-fpm or supervisor to configure.

| File | Purpose |
| --- | --- |
| `Dockerfile` | Production image (`base` → `vendor` → `runtime` stages) |
| `docker/Caddyfile` | Web server config; listens on `:8080`, TLS handled upstream |
| `docker/php.ini` | Production PHP + OPcache settings |
| `docker/entrypoint.sh` | Boot sequence: wait for DB → migrate → warm caches |
| `compose.yaml` | Deployment stack for Dokploy (pulls the published image) |
| `compose.local.yaml` | Same stack, but builds from source and publishes port 8080 |
| `.env.docker.example` | Every variable the stack needs |
| `.github/workflows/docker-publish.yml` | Builds + pushes to GHCR, then pings Dokploy |

## Image layout

- Serves `/app/public` on port **8080** as the unprivileged `www-data` user.
- Configuration comes from **real environment variables** — there is no `.env`
  inside the image, and `.env` is excluded from the build context.
- `CONTAINER_ROLE` selects behaviour: `app` (default, serves HTTP and owns
  migrations), `worker` (`queue:work`), `scheduler` (`schedule:work`). Only the
  `app` role runs migrations, so workers can be scaled without racing.
- `HEALTHCHECK` hits `GET /up`, which deliberately touches neither the database
  nor the cache — a datastore blip must not trigger a restart loop.
- `AUTO_MIGRATE=false` disables migrations on boot if you'd rather run them by
  hand.

Frontend assets are not built in the image: `public/index.css` and the Telescope
assets are committed, and `webpack.mix.js` currently compiles an empty
`resources/css/app.css` that no view references. Add a Node build stage if that
changes.

## Run it locally

```bash
cp .env.docker.example .env.docker
```

Fill in `APP_KEY`, `DB_PASSWORD` and `DB_ROOT_PASSWORD`. Generate the key with:

```bash
php artisan key:generate --show
```

Then bring the stack up on <http://localhost:8080>:

```bash
docker compose -f compose.local.yaml --env-file .env.docker up --build
```

## Deploy to Dokploy

**1. Publish the image.** Push to `main` and the `Docker` workflow builds
`ghcr.io/<owner>/shorter-is-better` and tags it `latest`, `main`,
`sha-<commit>`, plus `v<x.y.z>` for tags matching `v*.*.*`. Make the package
public in GitHub (*Packages → Package settings → Change visibility*), or add a
GHCR registry credential in Dokploy if you keep it private.

**2. Create the application.** In Dokploy: *Create Application → Compose*, point
it at this repository, and set the compose file to `compose.yaml`.

**3. Set the environment.** Paste the contents of `.env.docker.example` into the
application's *Environment* tab and fill in the blanks. `APP_KEY` and `APP_URL`
are required — the container refuses to start without a key rather than
generating a throwaway one that would invalidate every session on restart.

**4. Add the domain.** Attach your domain to the **`app`** service on port
**8080**. Dokploy's Traefik terminates TLS; the container trusts the
`X-Forwarded-*` headers it sends (see `app/Http/Middleware/TrustProxies.php`).

**5. Wire up auto-deploy.** Copy the webhook URL from the application's
*Deployments* tab into a GitHub Actions secret named `DOKPLOY_WEBHOOK_URL`. The
workflow's `deploy` job calls it after a successful push; without the secret the
job logs a notice and passes, so the build still works unconfigured.

## Operating notes

- **Persistent state.** `/app/storage` is a named volume. It holds
  `storage/settings.json`, which is where `anlutro/l4-settings` keeps the
  homepage copy and channel settings — losing that volume loses those settings.
- **Sessions and cache** use Redis, so the `app` service can be scaled to more
  than one replica. `mysql` and `redis` cannot.
- **Telescope** is off by default (`TELESCOPE_ENABLED=false`); it records every
  request and will grow the database quickly. The scheduler prunes entries
  older than 72h when it is on.
- **Artisan on a running stack:**
  ```bash
  docker compose exec app php artisan migrate:status
  ```
- **Architecture.** The workflow builds `linux/amd64` only. If you deploy to an
  arm64 host, add `linux/arm64` to `platforms` in the workflow — expect a much
  slower build, since the PHP extensions are compiled under emulation.
