# Docker & Dokploy deployment

The app ships as a single self-contained image built on
[FrankenPHP](https://frankenphp.dev) — PHP 8.4 and a Caddy web server in one
process, so there is no nginx, php-fpm or supervisor to configure.

| File | Purpose |
| --- | --- |
| `Dockerfile` | Production image (`base` → `vendor` → `assets` → `runtime` stages) |
| `docker/Caddyfile` | Web server config; listens on `:8080`, TLS handled upstream |
| `docker/php.ini` | Production PHP + OPcache settings |
| `docker/entrypoint.sh` | Boot sequence: wait for DB → migrate → warm caches |
| `compose.yaml` | Deployment stack for Dokploy (pulls the published image) |
| `compose.local.yaml` | Same stack, but builds from source and publishes port 8080 |
| `.env.docker.example` | Every variable the stack needs |
| `.github/workflows/release.yml` | Builds + pushes to GHCR, then pings Dokploy |

## Image layout

- Serves `/app/public` on port **8080** as the unprivileged `www-data` user.
  Application code is root-owned and read-only at runtime; only `storage/` and
  `bootstrap/cache` are writable.
- Configuration comes from **real environment variables** — there is no `.env`
  inside the image, and `.env` is excluded from the build context. Laravel's
  config/route/view/event caches are built by the entrypoint at container
  start, never at build time, so the image cannot freeze build-time values.
- `CONTAINER_ROLE` selects behaviour: `app` (default, serves HTTP and owns
  migrations), `worker` (`queue:work`), `scheduler` (`schedule:work`). Only the
  `app` role runs migrations, so workers can be scaled without racing.
- `HEALTHCHECK` hits `GET /up`, which deliberately touches neither the database
  nor the cache — a datastore blip must not trigger a restart loop.
- `RUN_MIGRATIONS=false` disables migrations on boot if you'd rather run them
  by hand. (This variable was previously named `AUTO_MIGRATE` — rename it if
  you had set it in Dokploy.)

Frontend assets are built **inside the image** by the `assets` stage: Tailwind
CSS 4 compiles `resources/css/app.css` into `public/index.css`. The stage needs
`vendor/` present first because the stylesheet `@import`s and `@source`s Flux
UI files from `vendor/livewire/flux` — that is why it copies vendor from the
composer stage. The `public/index.css` committed to the repo exists only for
non-Docker local development (`php artisan serve`); rebuild it with
`npm run build` when views or `resources/css/app.css` change.

## Process model

One image, one process per container — no supervisord. The stack deploys as a
single Dokploy Compose application, so each role is its own container: roles
restart and scale independently, logs are per-role, and a dead queue worker can
never hide behind a green web healthcheck.

- **`app`** — serves HTTP, owns migrations.
- **`worker`** — required: [Telegraph](https://defstudio.github.io/telegraph)
  sends every bot API call through a queued job
  (`SendRequestToTelegramJob`), and the stack runs `QUEUE_CONNECTION=redis`.
- **no `scheduler`** — `App\Console\Kernel::schedule()` is empty (has been
  since Telescope was removed), so the container would idle. If you add a
  scheduled task, add this service back to `compose.yaml`:

  ```yaml
  scheduler:
    <<: *app
    command: php artisan schedule:work
    environment:
      CONTAINER_ROLE: scheduler
      RUN_MIGRATIONS: "false"
    depends_on:
      app:
        condition: service_healthy
  ```

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

**1. Publish the image.** Push a `v*.*.*` tag (a digit must follow the `v` —
the workflow's tag filter rejects typos like `v.0.2.5`, which once shipped an
image under that name) and the `Release` workflow builds
`ghcr.io/<owner>/shorter-is-better` and tags it `latest`, `<x.y.z>`, `<x.y>` and
`sha-<commit>`. Pushes to `main` build nothing; pull requests build the image
without pushing it. Make the package public in GitHub (*Packages → Package
settings → Change visibility*), or add a GHCR registry credential in Dokploy if
you keep it private.

**2. Create the application.** In Dokploy: *Create Application → Compose*, point
it at this repository, and set the compose file to `compose.yaml`. The compose
file joins the external `dokploy-network`, which is how Dokploy's Traefik
reaches the `app` container; the datastores stay on the stack's private
default network.

**3. Set the environment.** Paste the contents of `.env.docker.example` into the
application's *Environment Settings* tab and fill in the blanks. Dokploy writes
that tab to a `.env` file beside the compose file, which `compose.yaml` both
interpolates and passes to the containers via `env_file` — so that tab is the
only place configuration lives. Apart from `IMAGE_TAG` (defaults to `latest`),
**`compose.yaml` defines no defaults**: a variable missing from the tab is a
variable the container does not have.

`APP_KEY` and `APP_URL` are required — the container refuses to start without a
key rather than generating a throwaway one that would invalidate every session
on restart. `DB_HOST` and `REDIS_HOST` must match the service names in
`compose.yaml` (`mariadb` and `redis`); they are the two values in that tab
that describe the stack's own topology rather than your preferences.

**4. Add the domain.** Attach your domain to the **`app`** service on port
**8080**. Dokploy's Traefik terminates TLS; the container trusts the
`X-Forwarded-*` headers it sends (see `app/Http/Middleware/TrustProxies.php`).

**5. Wire up auto-deploy.** The webhook URL from the application's
*Deployments* tab is stored as the GitHub Actions secret
`DOKPLOY_WEBHOOK_URL` — never inline it in the workflow, since this repository
is public and the URL is a live deploy trigger. Rotate it in Dokploy and
re-set it with:

```bash
gh secret set DOKPLOY_WEBHOOK_URL --repo MahdiMajidzadeh/shorter-is-better
```

The workflow's `deploy` job calls it after every successful non-PR build and
**fails loudly if the secret is missing or Dokploy refuses the call** — a
worker on the old image while the app is on the new one must never hide behind
a green tick. (A 404 from the webhook usually means the token was regenerated
in Dokploy — update the secret.)

### Upgrading an existing Dokploy deployment

One-time changes in the Dokploy dashboard when rolling out this revision:

1. In *Environment Settings*, delete `APP_IMAGE` (the image name is now pinned
   in `compose.yaml`) and optionally add `IMAGE_TAG=latest`.
2. If you had set `AUTO_MIGRATE`, rename it to `RUN_MIGRATIONS`.
3. Nothing else changes: same webhook, same domain mapping, same volumes.

### Release flow

| Trigger | Image tags published | Dokploy |
| --- | --- | --- |
| Push tag `v1.2.3` / publish a release | `latest`, `1.2.3`, `1.2`, `sha-<commit>` | Redeployed via webhook |
| `workflow_dispatch` with an existing tag | same, for that tag | Redeployed via webhook |
| Push to `main` | none — the workflow does not run | Untouched |
| Pull request | none (build only) | Untouched |

Releasing is therefore one command:

```bash
git tag v1.2.3 && git push origin v1.2.3
```

or, because bare tag pushes have not always produced push events reliably:

```bash
gh release create v1.2.3 --generate-notes
```

To roll back, set `IMAGE_TAG` to a pinned release (e.g. `1.2.2`) in Dokploy's
Environment Settings and redeploy.

`:latest` is mutable, so the app services set `pull_policy: always`. Without it
a redeploy reuses the copy already cached on the host and quietly ships the
previous build.

## Operating notes

- **Persistent state.** `/app/storage` is a named volume. It holds
  `storage/settings.json`, which is where `anlutro/l4-settings` keeps the
  homepage copy and channel settings — losing that volume loses those settings.
- **Sessions and cache** use Redis, so the `app` service can be scaled to more
  than one replica. `mariadb` and `redis` cannot.
- **Artisan on a running stack:**
  ```bash
  docker compose exec app php artisan migrate:status
  ```
- **Architecture.** The workflow builds `linux/amd64` only. If you deploy to an
  arm64 host, add `linux/arm64` to `platforms` in the workflow — expect a much
  slower build, since the PHP extensions are compiled under emulation.
