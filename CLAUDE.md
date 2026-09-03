# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A self-hosted URL shortener (Laravel 13 / PHP 8.4) with a web panel and a Telegram bot front end. Shortening and click tracking come from `ashallendesign/short-url`; the bot is built on `defstudio/telegraph`. README.md covers user-facing setup; DOCKER.md covers the production image and the Dokploy release pipeline in depth.

## Commands

```bash
composer install && cp .env.example .env && php artisan key:generate
php artisan migrate --seed          # seeds one "admin" user; ADMIN_PASSWORD in .env sets its password
php artisan serve                   # panel at /panel, login at /auth
```

```bash
php artisan test                                        # whole suite (in-memory SQLite, see phpunit.xml)
php artisan test tests/Feature/DashboardTest.php        # one file
php artisan test --filter=test_referrers_are_grouped_by_host   # one test
./vendor/bin/pint                                       # format (run before committing)
```

```bash
npm run build     # Tailwind 4: resources/css/app.css -> public/index.css (committed)
npm run watch
php artisan bot:update   # re-register webhook + command list on every bot; run after changing bot_commands()
```

Docker locally: `docker compose -f compose.local.yaml --env-file .env.docker up --build` (needs a gitignored `.env.docker` with `APP_KEY`, `DB_PASSWORD`, `DB_ROOT_PASSWORD`).

Release: push a `vX.Y.Z` tag (or `gh release create vX.Y.Z --generate-notes`). Pushing to `main` builds nothing. See `.github/workflows/release.yml`.

## Code style

Laravel Pint with two non-default rules in `pint.json`: imports are sorted by **line length**, not alphabetically, and `=>` in arrays is aligned. Existing files follow this; keep it when adding imports or array literals.

## Architecture

### Data ownership

The app defines only `User`. Short links and visits use the package models directly (`AshAllenDesign\ShortURL\Models\ShortURL` / `ShortURLVisit`), and bots/chats use `TelegraphBot` / `TelegraphChat`. Package tables are migrated from `database/migrations` (copied in, not published on the fly), and `telegraph_chats` is extended with `hash` and `user_id` columns that bind a chat to a panel user.

All analytics queries exclude bot traffic with `device_type != 'robot'`; keep that filter when adding stats.

Runtime settings (homepage copy, `channel.has` / `channel.id` / `channel.username`) live in `storage/settings.json` via `anlutro/l4-settings` and the `setting()` helper, not in the database or `.env`.

### Web panel

Plain controllers + Blade, routes in `routes/web.php`. Everything under `/panel`, `/links`, `/settings` sits behind the `auth` middleware. The redirect route `/s/{key}` is registered by the short-url package; its prefix and tracking options are in `config/short-url.php`. `GET /up` is the Docker healthcheck and deliberately touches no datastore.

Views extend `template/master` (public) or `template/dash` (panel sidebar). The UI is Flux UI used purely as Blade components (`<flux:...>`); there are no Livewire components in `app/`. The Tailwind build `@import`s and `@source`s files from `vendor/livewire/flux`, so `vendor/` must exist before `npm run build` works.

### Telegram bot (state machine)

`config/telegraph.php` points the webhook at `App\Http\Webhooks\Handler`. Flow:

1. Each slash command is a public method on `Handler` (`short`, `short_key`, `stat`, `report`, `for_channel`) that calls `startState(SomeState::class)`. Bare messages with no active state default to `Short` at step 2.
2. A state is a `StateManager` subclass in `app/Http/Webhooks/State/` with `handleStep1()`, `handleStep2()`, … . Conversation position is kept in Telegraph's per-chat storage under `state`, `step`, and `data.*`; `nextStep()` advances, `done()` clears.
3. Inline-keyboard buttons route through `Handler::keyboard_handler()`, which maps `action_name` params (e.g. `channel_confirm`) to a state class and passes the action name in as the "message" (see `ForChannel::handleStep3`).
4. Authorization: `Handler::isAuthenticated()` requires `telegraph_chats.user_id` to be set. `/start` issues a hash; visiting `/auth/bot/{hash}` while logged in binds the chat. Negative chat IDs (groups/channels) are ignored in `handleChatMessage`.

The advertised command list is `bot_commands()` in `app/helpers.php` (autoloaded via composer). Adding a command means: a `Handler` method, a state class, an entry in `bot_commands()`, then `php artisan bot:update`. Note `bulk` is advertised there but has no `Handler` method (known gap; bulk works in the panel only).

Telegraph sends every Telegram API call through a queued job. Locally `QUEUE_CONNECTION=sync` so it just works; in production the queue is Redis and the supervised `worker` program in `docker/supervisord.conf` must be running or the bot goes silent.

### Deployment shape

One FrankenPHP image, one container, supervisord as PID 1 running `web` + `worker`. Config comes from real environment variables set in Dokploy; Laravel caches are built by `docker/entrypoint.sh` at container start, never at build time. `App\Console\Kernel::schedule()` is empty and there is no `scheduler` program; if you add a scheduled task, also add the supervisord program shown in DOCKER.md. `/app/storage` is a persistent volume because it holds `settings.json`.

## Tests

`phpunit.xml` forces SQLite `:memory:`, array cache/session, and sync queue. Feature tests use `RefreshDatabase` and build a `User` by hand in `setUp()`. The short-url package's migrations run too, so `ShortURL::destinationUrl(...)->make()` and `ShortURLVisit` can be used directly, as in `DashboardTest`.
