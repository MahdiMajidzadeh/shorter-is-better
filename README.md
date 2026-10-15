# Shorter is Better

A personal, self-hosted link shortener for publishing: several short domains,
Telegram channels with their own post templates, a Telegram bot and mini app,
a scoped JSON API, click reports, and a Persian right-to-left panel.

Built with Laravel 13 / PHP 8.4. Shortening and click tracking are the app's
own code; the bot runs on [Nutgram](https://nutgram.dev); API keys are
[Laravel Sanctum](https://laravel.com/docs/sanctum) tokens; the panel is
[Flux UI](https://fluxui.dev) on Tailwind CSS 4.

## What it does

- **Projects.** A project is one short domain plus a root redirect url. Links
  are served from `/s/{key}` on that domain; the bare domain redirects wherever
  you set. Keys are unique across all projects, so a link resolves on any of
  your domains.
- **Channels.** A Telegram channel with a language (Persian or English) and an
  editable post template: message text with placeholders such as `{title}`,
  `{short_url}` and `{feed_url}`, plus inline buttons. Channels and projects are
  many-to-many; each project marks one as default.
- **Users.** Admins see everything and manage projects, channels, users, the
  bot and its chats. Other users see the projects they were granted, in the
  panel, the bot, the API and the mini app alike.
- **Bot.** One bot for every project: `/short`, `/short_key`, `/bulk`, `/stat`,
  `/report`, `/project` to switch the active project, `/for_channel` and
  `/feed` to post an article (with a preview you can confirm, edit or dismiss).
  A bare url is shortened on the spot. Chats must be approved by an admin.
- **Mini app.** The bot's menu button opens a small app with the same four
  things: shorten, links and stats, post to a channel, report.
- **API.** `/api/v1` with per-project keys that carry scopes.
- **Reports.** Clicks per day, per link, per source (panel, bot, API, mini
  app) and per channel, with robot traffic excluded. Titles are fetched from
  the page automatically.
- **Persian first.** The panel, bot replies and mini app are Persian and
  right-to-left by default, with Jalali dates; every user can switch to
  English.

## Local setup

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Point the `DB_*` variables in `.env` at MySQL or MariaDB, then:

```bash
php artisan migrate --seed
```

The seeder creates one admin account (username `admin`, password from
`ADMIN_PASSWORD` or a generated one printed once) and the first project from
`APP_URL`.

```bash
php artisan serve
```

Sign in at <http://localhost:8000/auth>. The panel is at `/panel`.

### Docker

A single FrankenPHP image with supervisord running the web server and the queue
worker. Create a gitignored `.env.docker` with `APP_KEY`, `DB_PASSWORD` and
`DB_ROOT_PASSWORD` (snippet at the top of [compose.local.yaml](compose.local.yaml)),
then:

```bash
docker compose -f compose.local.yaml --env-file .env.docker up --build
```

Production, the Dokploy release pipeline and every environment variable are
in [DOCKER.md](DOCKER.md).

## Projects and domains

Create projects under **Administration → Projects**. For each extra domain:
point its DNS at the server, add the domain to the app in Dokploy (so Traefik
issues a certificate and routes it to the container), then enter the domain on
the project. The panel, the API and the mini app answer only on the primary
`APP_URL` domain; every other project domain serves just the redirects.

## Telegram bot

1. Create a bot with [@BotFather](https://t.me/BotFather) and put its token in
   the `TELEGRAM_TOKEN` environment variable.
2. Run `php artisan bot:update`, or press **Register webhook and commands** on
   **Administration → Bot**. This sets the webhook (`/api/telegram/webhook`),
   the command list in Persian and English, and the mini app menu button.
   `APP_URL` must be a public HTTPS url; tunnel it for local work.
3. Each user sends `/start` to the bot and opens the link it replies with while
   signed in to the panel. An admin then approves the chat under
   **Administration → Bot access** (admins' own chats are approved at once).

To post to a channel: add the bot to the channel as an admin, create the
channel under **Administration → Channels** with its numeric chat id, and
attach it to a project. The template editor shows a live preview.

## API

Create a key under **API keys** (per project, shown once) and send it as
`Authorization: Bearer <key>`. Scopes: `links:create`, `links:read`,
`links:stats`, `links:bulk`, `links:delete`, `reports:read`.

| Method | Path | Scope |
| --- | --- | --- |
| GET | `/api/v1/me` | any |
| GET | `/api/v1/links?search=&per_page=` | `links:read` |
| POST | `/api/v1/links` `{url, key?}` | `links:create` |
| POST | `/api/v1/links/bulk` `{text}` | `links:bulk` |
| GET | `/api/v1/links/{key}` | `links:read` |
| GET | `/api/v1/links/{key}/stats?days=30` | `links:stats` |
| DELETE | `/api/v1/links/{key}` | `links:delete` |
| GET | `/api/v1/reports/summary?days=7` | `reports:read` |

Errors always look like `{"error": {"code": "...", "message": "...", "fields"?: {...}}}`.
120 requests per minute per key. A freshly created link may carry `title: null`
until the queue worker has fetched the page.

## Configuration

| Key | Purpose |
| --- | --- |
| `APP_URL` | Primary domain; the panel, API and mini app live here. |
| `TELEGRAM_TOKEN` | The bot token. The bot and the mini app refuse to run without it. |
| `ADMIN_PASSWORD` | Password of the seeded admin. |
| `SANCTUM_TOKEN_PREFIX` | Prefix of API keys, default `sib_`. |
| `SENTRY_LARAVEL_DSN` | Optional error reporting. |

Key length, alphabet and the title fetch timeout are in
[config/shortener.php](config/shortener.php). Everything else (projects,
channels, templates) is edited in the panel and stored in the database.

## Development

```bash
./vendor/bin/pint
```

```bash
php artisan test
```

The suite runs on in-memory SQLite and covers the panel, redirects, the
shortener, the bot (against Nutgram's fake), the API and the mini app.
Architecture notes for contributors are in [CLAUDE.md](CLAUDE.md); the
transformation plan and first-deploy checklist are in [docs/PLAN.md](docs/PLAN.md).

`public/index.css` is committed for non-Docker local development; rebuild it
after changing views or `resources/css/app.css`:

```bash
npm install && npm run build
```
