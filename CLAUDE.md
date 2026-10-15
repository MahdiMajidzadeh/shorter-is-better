# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A personal, multi-project URL shortener (Laravel 13 / PHP 8.4) with a web panel and a Telegram bot front end. Shortening and click tracking are our own code (`app/Services`); the bot is built on `defstudio/telegraph` (being replaced by Nutgram in phase 2 of docs/PLAN.md). README.md covers user-facing setup; DOCKER.md covers the production image and the Dokploy release pipeline in depth. docs/PLAN.md is the approved transformation plan and records which phase is done.

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

- `Project` = one short domain (`domain` is a bare host) + root redirect url. `Link` (table `short_urls`) and `Visit` (table `short_url_visits`) are our models on the tables the old short-url package created; every historical row is kept. Links carry `project_id`, `user_id`, `source` (panel/bot/api/miniapp) and an auto-fetched `title`.
- `Channel` holds a Telegram chat id, `language` (fa/en) and a post template (`template_text` + `template_buttons`). Projects and channels are many-to-many through `channel_project` with an `is_default` flag per project.
- Users are admins (see everything) or regular users granted projects through `project_user`. `User::accessibleProjects()` and `canAccessProject()` are the only access checks; use them.
- Bots/chats still use `TelegraphBot` / `TelegraphChat`; `telegraph_chats` has `hash` and `user_id` columns binding a chat to a panel user.
- `App\Support\LegacyImport` (run by a migration) turned the old single-domain install into project 1 and imported the channel from the old `storage/settings.json`. There are no runtime settings any more.

All link creation goes through `App\Services\Shortener::make()` / `bulk()`, which sets the project domain, source and title job. Clicks are recorded by `VisitRecorder` with the same whichbrowser mapping as before. Analytics exclude robots with `Visit::human()` (null device type counts as human); keep that scope when adding stats.

The panel's current project comes from the `project` middleware (`SetProjectContext`): session `project_id`, validated against the user's access, shared to views as `$currentProject` / `$projects`, and read in controllers through `$this->project()`.

### Web panel

Plain controllers + Blade, routes in `routes/web.php`. `/` (`RootController`: project root redirect by host) and `/s/{key}` (`RedirectController`) answer on every project domain; everything else is behind `auth` + `project`, and `/projects`, `/channels`, `/users`, `/settings` additionally behind `admin`. Key generation and prefix live in `config/shortener.php`. `GET /up` is the Docker healthcheck and deliberately touches no datastore.

Views extend `template/master` (public) or `template/dash` (panel sidebar). The UI is Flux UI used purely as Blade components (`<flux:...>`); there are no Livewire components in `app/`. The Tailwind build `@import`s and `@source`s files from `vendor/livewire/flux`, so `vendor/` must exist before `npm run build` works.

The panel is Persian and RTL by default (`config('app.locale') = fa`, per-user `locale` column). Every user-facing string goes through `__()` with the English text as the key and its translation in `lang/fa.json`. Dates and numbers go through the `local_date()` / `local_number()` helpers (Jalali and Persian digits in `fa`). Mark url/code inputs and cells `dir="ltr"`.

### Telegram bot (state machine)

`config/telegraph.php` points the webhook at `App\Http\Webhooks\Handler`. Flow:

1. Each slash command is a public method on `Handler` (`short`, `short_key`, `stat`, `report`, `for_channel`, `feed`) that calls `startState(SomeState::class)`. Bare messages with no active state default to `Short` at step 2.
2. A state is a `StateManager` subclass in `app/Http/Webhooks/State/` with `handleStep1()`, `handleStep2()`, … . Conversation position is kept in Telegraph's per-chat storage under `state`, `step`, and `data.*`; `nextStep()` advances, `done()` clears.
3. Inline-keyboard buttons route through `Handler::keyboard_handler()`, which maps `action_name` params (e.g. `channel_confirm`) to a state class and passes the action name in as the "message" (see `ForChannel::handleKeyboard`). `Feed` extends `ForChannel` with a `feed_` action prefix and one extra step for the feed url.
4. Authorization: `Handler::isAuthenticated()` requires `telegraph_chats.user_id` to be set. `/start` issues a hash; visiting `/auth/bot/{hash}` while logged in binds the chat. Negative chat IDs (groups/channels) are ignored in `handleChatMessage`.
5. Project: until chats carry their own project (phase 2), `StateManager::project()` is the bound user's first accessible project. Channel posts go to that project's default channel and are rendered by `PostComposer` from the channel's template.

The advertised command list is `bot_commands()` in `app/helpers.php` (autoloaded via composer). Adding a command means: a `Handler` method, a state class, an entry in `bot_commands()`, then `php artisan bot:update`. Note `bulk` is advertised there but has no `Handler` method (known gap; bulk works in the panel only).

Telegraph sends every Telegram API call through a queued job. Locally `QUEUE_CONNECTION=sync` so it just works; in production the queue is Redis and the supervised `worker` program in `docker/supervisord.conf` must be running or the bot goes silent.

### Deployment shape

One FrankenPHP image, one container, supervisord as PID 1 running `web` + `worker`. Config comes from real environment variables set in Dokploy; Laravel caches are built by `docker/entrypoint.sh` at container start, never at build time. `App\Console\Kernel::schedule()` is empty and there is no `scheduler` program; if you add a scheduled task, also add the supervisord program shown in DOCKER.md. `/app/storage` is a persistent volume because it holds `settings.json`.

## Tests

`phpunit.xml` forces SQLite `:memory:`, array cache/session, and sync queue. Feature tests use `RefreshDatabase` and the helpers on `tests/TestCase.php` (`makeAdmin()`, `makeUser()`, `makeProject()`, `makeChannel()`, `makeLink()`, `makeVisit()`). A fresh test database has no project; create one. `Http::fake()` whatever fetches titles, or the sync queue will hit the network.
