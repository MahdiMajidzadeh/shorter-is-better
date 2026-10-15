# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A personal, multi-project URL shortener (Laravel 13 / PHP 8.4) with a web panel and a Telegram bot front end. Shortening and click tracking are our own code (`app/Services`); the bot is built on Nutgram (`nutgram/laravel`). README.md covers user-facing setup; DOCKER.md covers the production image and the Dokploy release pipeline in depth. docs/PLAN.md is the approved transformation plan and records which phase is done.

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
php artisan bot:update   # register the webhook + per-language command list; run after setting TELEGRAM_TOKEN or changing command descriptions
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
- `BotChat` is every private Telegram chat that ever wrote to the bot: `status` (pending/active/blocked), bound `user_id`, the chat's active `project_id`, and the `hash` used by the `/auth/bot/{hash}` link. The old `telegraph_*` tables are kept but unused.
- `App\Support\LegacyImport` (run by a migration) turned the old single-domain install into project 1 and imported the channel from the old `storage/settings.json`. There are no runtime settings any more.

All link creation goes through `App\Services\Shortener::make()` / `bulk()`, which sets the project domain, source and title job. Clicks are recorded by `VisitRecorder` with the same whichbrowser mapping as before. Analytics exclude robots with `Visit::human()` (null device type counts as human); keep that scope when adding stats.

The panel's current project comes from the `project` middleware (`SetProjectContext`): session `project_id`, validated against the user's access, shared to views as `$currentProject` / `$projects`, and read in controllers through `$this->project()`.

### Web panel

Plain controllers + Blade, routes in `routes/web.php`. `/` (`RootController`: project root redirect by host) and `/s/{key}` (`RedirectController`) answer on every project domain; everything else is behind `auth` + `project`, and `/projects`, `/channels`, `/users`, `/settings` additionally behind `admin`. Key generation and prefix live in `config/shortener.php`. `GET /up` is the Docker healthcheck and deliberately touches no datastore.

Views extend `template/master` (public) or `template/dash` (panel sidebar). The UI is Flux UI used purely as Blade components (`<flux:...>`); there are no Livewire components in `app/`. The Tailwind build `@import`s and `@source`s files from `vendor/livewire/flux`, so `vendor/` must exist before `npm run build` works.

The panel is Persian and RTL by default (`config('app.locale') = fa`, per-user `locale` column). Every user-facing string goes through `__()` with the English text as the key and its translation in `lang/fa.json`. Dates and numbers go through the `local_date()` / `local_number()` helpers (Jalali and Persian digits in `fa`). Mark url/code inputs and cells `dir="ltr"`.

### Telegram bot (Nutgram)

One bot, token in `TELEGRAM_TOKEN`, webhook at `POST /api/telegram/webhook` (`TelegramWebhookController`, secret token enforced in production by Nutgram safe mode). Handlers live in `routes/telegram.php`:

1. Global middleware, in order: `ResolveBotChat` (ignores non-private chats, upserts the `BotChat`, sets the locale from the bound user, exposes the row as `$bot->get('botChat')`) then `EnsureBotAccess` (silence for blocked, `bot_replies.not_allowed` for anything not active + bound to an active user, `bot_replies.no_project` when the user has no project). `/start` skips the gate so a new chat can get its linking url.
2. Each command maps to a handler in `app/Telegram/Handlers` or a `Conversation` in `app/Telegram/Conversations` (`Short`, `ShortWithKey`, `Bulk`, `Stat`, `ForChannel`, `Feed`). Conversation state is serialized to the cache between steps (array in tests, Redis in production); keep step data in protected properties. `InteractsWithBotChat` gives steps the chat, user and project.
3. `ForChannel` picks a channel (inline keyboard when the project has several, else the default), shortens the url on the chat's active project, fetches page meta, renders the channel template with `PostComposer`, previews with Confirm / Edit / Dismiss callback buttons (`post:*`), and publishes through `ChannelPublisher`. `Feed` is the same with one extra feed-url step.
4. `/project` lists the user's projects as `project:{id}` callback buttons and stores the choice on the chat. A bare url outside a conversation is shortened by `FallbackHandler`.
5. Every reply string is in `lang/{fa,en}/bot_replies.php` (the group is deliberately not named after a UI label: `__('Bot')` on a case-insensitive filesystem would resolve a `bot.php` group). Command descriptions are set per language in `routes/telegram.php`; `php artisan bot:update` registers webhook + commands.

Bot replies are synchronous HTTP calls; the only queued work left is `FetchLinkTitle`, so the supervised `worker` program in `docker/supervisord.conf` must still run or titles stay empty.

Adding a command means: a handler or conversation class, an `onCommand` line with descriptions in `routes/telegram.php`, strings in `bot_replies`, a test in `tests/Feature/BotTest.php`, then `php artisan bot:update`.

Access is granted in the panel under Bot access (`/bot/chats`): approve = bind a user + status active. The `/auth/bot/{hash}` link binds the chat to the signed-in user as pending (active when that user is an admin).

### Deployment shape

One FrankenPHP image, one container, supervisord as PID 1 running `web` + `worker`. Config comes from real environment variables set in Dokploy; Laravel caches are built by `docker/entrypoint.sh` at container start, never at build time. `App\Console\Kernel::schedule()` is empty and there is no `scheduler` program; if you add a scheduled task, also add the supervisord program shown in DOCKER.md. `/app/storage` is a persistent volume because it holds `settings.json`.

## Tests

`phpunit.xml` forces SQLite `:memory:`, array cache/session, and sync queue. Feature tests use `RefreshDatabase` and the helpers on `tests/TestCase.php` (`makeAdmin()`, `makeUser()`, `makeProject()`, `makeChannel()`, `makeLink()`, `makeVisit()`). A fresh test database has no project; create one. `Http::fake()` whatever fetches titles, or the sync queue will hit the network. Bot tests drive the Nutgram fake (`app(Nutgram::class)`) with `hearMessage([...])->reply()` and a fixed private chat id, then `assertReplyText()` / `assertReply('sendMessage', [...], $index)`; see `BotTest::say()` and `tap()`.
