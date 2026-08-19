# Shorter is Better

A self-hosted URL shortener with a stats dashboard and a Telegram bot front end.
Shorten links from a web panel or straight from a Telegram chat, then see who
clicked them — browsers, devices, operating systems and daily traffic — without
handing your link data to a third party.

Built with Laravel 13 / PHP 8.4 on top of
[ashallendesign/short-url](https://github.com/ash-jc-allen/short-url) for the
shortening and click tracking, and [defstudio/telegraph](https://github.com/defstudio/telegraph)
for the bot. The UI is [Flux UI](https://fluxui.dev) (free tier) on Tailwind
CSS 4, with automatic light/dark mode.

## Features

- **Short links** — auto-generated keys or your own custom key, served from
  `/s/{key}` with a 301 redirect.
- **Bulk shortening** — paste a block of text and every URL in it comes back
  replaced with its short version, surrounding text intact.
- **Click analytics** — per-link breakdown of browser, device type and operating
  system, plus a 30-day views chart. Robot traffic is excluded from reports.
- **Visit log** — a paginated, chronological feed of every recorded click.
- **Telegram bot** — shorten links, fetch stats and pull a 7-day report from
  chat. Bots are created and registered from the settings page; no CLI needed.
- **Channel publishing** — hand the bot a URL and it scrapes the page's
  OpenGraph title, description and image, shows you a preview with
  Confirm / Edit / Dismiss buttons, and posts the approved version to your
  Telegram channel with the short link attached.
- **Editable landing page** — the public homepage's headline, subtitle and
  call-to-action are all set from the settings page.

## How it works

| Piece | Where |
| --- | --- |
| Web panel (dashboard, links, settings) | [routes/web.php](routes/web.php) |
| Redirect route `/s/{key}` | registered by the short-url package, prefix set in [config/short-url.php](config/short-url.php) |
| Health probe `GET /up` | [HealthController.php](app/Http/Controllers/HealthController.php) |
| Telegram command routing | [app/Http/Webhooks/Handler.php](app/Http/Webhooks/Handler.php) |
| Bot conversation steps | [app/Http/Webhooks/State/](app/Http/Webhooks/State) |

The bot is a small state machine. `Handler` maps each slash command to a state
class, and [StateManager](app/Http/Webhooks/StateManager.php) walks the chat
through numbered steps (`handleStep1`, `handleStep2`, …) using Telegraph's
per-chat storage, so a multi-turn exchange like "send your url" → "short key:"
needs no session table of its own.

Bots are locked to a panel user. Sending `/start` returns a one-time
`/auth/bot/{hash}` link; opening it while signed in to the panel binds that chat
to your account, and unbound chats get a "Not Allowed" reply instead of a short
link. Group and channel chats (negative chat IDs) are ignored outright.

### Bot commands

| Command | Does |
| --- | --- |
| `/short` | shorten a URL (also the default for any bare message) |
| `/short_key` | shorten a URL with a custom key |
| `/stat` | browser / device / OS breakdown and 14-day views for one short URL |
| `/report` | top 20 links and daily non-bot views for the last 7 days |
| `/for_channel` | build and publish a channel post — only when a channel is configured |

`bot_commands()` also advertises `/bulk`, but `Handler` has no method for it, so the
bot replies with its unknown-command message. Bulk shortening works in the panel
only.

## Requirements

- PHP 8.4+
- MySQL or MariaDB
- Composer
- Node.js (only if you change frontend assets — `public/index.css` is committed)

## Local setup

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Point the `DB_*` variables in `.env` at your database, then:

```bash
php artisan migrate --seed
```

The seeder creates one admin account (username `admin`). Set `ADMIN_PASSWORD` in
`.env` before seeding to pick its password; left empty, the seeder generates a
random one and prints it once in the seed output.

```bash
php artisan serve
```

Sign in at <http://localhost:8000/auth> and the panel is at `/panel`.

### Docker

A single-image FrankenPHP stack (PHP 8.4 + Caddy in one process) is ready to go:

```bash
cp .env.docker.example .env.docker
docker compose -f compose.local.yaml --env-file .env.docker up --build
```

Fill in `APP_KEY`, `DB_PASSWORD` and `DB_ROOT_PASSWORD` first. Full details,
including the GitHub Actions → Dokploy release pipeline, are in
[DOCKER.md](DOCKER.md).

## Connecting a Telegram bot

1. Create a bot with [@BotFather](https://t.me/BotFather) and copy the token.
2. In the panel, go to **Settings → Create Bot** and paste the token and a name.
   The app registers the webhook and the command list for you.
3. Message your bot `/start` and open the link it sends back while signed in to
   the panel.

The webhook has to be reachable from Telegram, so `APP_URL` must be a public
HTTPS URL — for local work, tunnel it (ngrok, Cloudflare Tunnel) and re-run:

```bash
php artisan bot:update
```

That re-registers the webhook and refreshes the command list on every bot, which
is also what you want after changing the available commands in
[bot_commands()](app/helpers.php).

To publish to a channel, add the bot to the channel as an admin and fill in the
channel ID and username under **Settings → Channel**. Enabling it adds
`/for_channel` to the command list.

## Configuration

Runtime settings — homepage copy, channel details — live in
`storage/settings.json` via [anlutro/l4-settings](https://github.com/anlutro/laravel-settings),
edited from the settings page rather than the environment. **Back that file up**:
it is not in the database, and in Docker it lives on the `storage` volume.

Shortening behaviour (key length, tracked fields, redirect status code, the `/s`
prefix) is in [config/short-url.php](config/short-url.php).

Optional error reporting: set `SENTRY_LARAVEL_DSN`.

## Development

```bash
./vendor/bin/pint
```

```bash
php artisan test
```

Code style is [Laravel Pint](https://laravel.com/docs/pint) with the rules in
[pint.json](pint.json). The test suite runs on an in-memory SQLite database and
covers page rendering, auth redirects and the short-link redirect.

`public/index.css` is committed, so Node is only needed when you change views
or [resources/css/app.css](resources/css/app.css) — rebuild it with:

```bash
npm install && npm run build
```

(`npm run watch` rebuilds on change while you work.)
