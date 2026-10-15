# Transformation plan: personal multi-project shortener

Goal: turn the general-purpose shortener into a personal publishing tool.
Several short domains and Telegram channels, one bot, one panel, a scoped
API, a Telegram mini app, on-demand reports, full Persian RTL.

Hard constraints (never changed): the `/s/{key}` prefix, every existing
`url_key`, every stored `default_short_url`, and every `short_url_visits`
row. The `short_urls` and `short_url_visits` tables are kept and only gain
columns. All migrations are additive.

## Deploying v2 the first time

1. Run the local Docker compose once (`compose.local.yaml`) so MySQL executes
   the new migrations before a production tag; so far they have only run on
   SQLite. The entrypoint runs `migrate --force` on container start.
2. Set `TELEGRAM_TOKEN` in Dokploy. The panel's Bot page shows the token of
   the bot created in the old version, ready to copy.
3. After the first start, press "Register webhook and commands" on the Bot
   page, or run `php artisan bot:update`. The webhook path changed to
   `/api/telegram/webhook`.
4. Open the imported channel under Channels and switch it to Persian if you
   want the right-to-left marks; it was imported as English so the post
   stays identical.
5. For every extra short domain: point DNS at the server, add the domain to
   the app in Dokploy, then create the project with that domain in the panel.
6. Expect dashboard totals to shift slightly: visits with no device type
   now count as human. Channel attribution starts from this deploy; older
   bot links show under "not posted to a channel".
7. Existing bot chats keep working if they were bound to a user; other chats
   must send /start and be approved under Bot access.
8. The mini app refuses to run until `TELEGRAM_TOKEN` is set, and Telegram
   only opens it over HTTPS, so it works on the deployed primary domain
   only. The menu button that opens it is registered by step 3.

## Decisions taken from your answers

| Topic | Decision |
| --- | --- |
| Mini app | Companion, not main UI. Reuses panel pages where they fit a phone, compact views where they do not. |
| API | Keys with per-key scopes: `links:create`, `links:read`, `links:stats`, `links:bulk`, `links:delete`, `reports:read`. A key is bound to one project. |
| Projects | A **project** owns one short domain, a root redirect URL, and its settings. One bot serves every project. |
| Channels | Projects and channels are **many-to-many**. A channel can receive posts from several projects, a project can post to several channels. Each project marks one of its channels as default. |
| Channel language | Each channel is `fa` or `en`. It controls: the template seeded when the channel is created, the RTL or LTR direction marks wrapped around the post text, the number and date format in the post, and the language of the preview the bot shows before posting. Template text itself is yours to write in any language. |
| Channel template | Each channel has an editable post template: message text with placeholders, and a list of inline buttons (label + URL template). |
| Users | Admin manages users and sees everything. Other users are granted projects and see only those in panel, bot, API and mini app. |
| Bot access | Admin controls who can use the bot. A chat must be approved and bound to a user from the panel. Unknown chats get a short refusal. |
| Reports | On demand only, from panel, bot, API, mini app. No scheduler. |
| Link metadata | Per link: project, creator, source (`panel`, `bot`, `api`, `miniapp`), auto-fetched page title. Nothing else. |
| Landing page | Removed. Root of each domain redirects to that project's configured URL. |
| Language | Persian, RTL, throughout (see open assumption 1). |

## Package decisions

Checked against Laravel 13 and PHP 8.4 with a `composer require --dry-run`;
everything below resolves.

| Package | Decision | Why |
| --- | --- | --- |
| `ashallendesign/short-url` | **Replace with our own code** | It gives key generation, visit recording and a redirect route, all of which we need to own anyway for per-project domains and per-link metadata. Keeping it means a side table, a global `default_url` hack and a config file that has already drifted from the vendor copy. Our `Link` and `Visit` models sit on the existing tables, so every URL and visit row survives untouched. |
| `whichbrowser/parser` | **Add directly** | The user-agent parser short-url uses. Reusing it with the same desktop / mobile / tablet / robot mapping keeps new visit rows classified exactly like old ones, so the `device_type != 'robot'` filter stays correct across the cutover. |
| `defstudio/telegraph` | **Replace with `nutgram/laravel`** | Phase 2 rewrites the whole bot around projects regardless. Nutgram gives class-based conversations in place of the hand-rolled state machine, middleware as the natural place for the pending / active / blocked gate, and built-in mini app `initData` validation. Bot chats move to our own `bot_chats` table; a migration copies `chat_id`, `hash`, `user_id` from `telegraph_chats`. Sends become synchronous HTTP calls, so the "worker down, bot silent" failure mode disappears. |
| `anlutro/l4-settings` | **Drop** | After the data migration nothing lives in `settings.json`. Projects, channels and the root redirect are rows. |
| `shweshi/opengraph`, `symfony/browser-kit` | **Drop** | Title and description are fetched in a queued job with Laravel's Http client and an explicit timeout. `Goutte\Client` is imported in `ForChannel` but not even installed. |
| `laravel/sanctum` | **Add** | API keys with abilities as scopes, created per project in the panel. |
| `morilog/jalali` | **Add** | Jalali dates in panel, bot and mini app. API stays Gregorian ISO 8601. |
| `livewire/flux` | **Keep** | Already in place, carries RTL variants for sidebar and pagination. |
| `sentry/sentry-laravel`, `malios/php-to-ascii-table` | **Keep** | Unchanged roles. |

## Open assumptions (confirm or correct)

1. **Persian RTL applies to everything**: panel, bot replies, mini app, and
   channel posts (subject to each channel's language). Not only the posts.
2. **Short keys stay globally unique** across all domains. The redirect
   route answers on any domain, so an old link keeps working even if its
   domain later moves between projects.
3. **Panel, API and mini app live only on the primary `APP_URL` domain.**
   Every other project domain serves only `/s/{key}` and the root redirect.
   This keeps sessions, CSRF and the bot auth link single-host.
4. **One shared channel, posted to from a chat, gets the short link on the
   chat's active project domain.** The channel does not pick the domain.

## Data model

Existing tables, columns added:

- `short_urls`: `project_id`, `user_id`, `source`, `title`. Rows become the
  `Link` model. Columns the old package wrote (`single_use`, `track_*`,
  `activated_at`, …) stay in place and are ignored.
- `short_url_visits`: unchanged, becomes the `Visit` model.
- `users`: `locale` (`fa` default).

New tables:

- `projects`: name, domain, root_redirect_url, timestamps.
- `channels`: chat_id, username, language, template_text,
  template_buttons (JSON list of `{label, url}`).
  Placeholders in text and button URLs: `{title}`, `{description}`,
  `{short_url}`, `{original_url}`, `{feed_url}`, `{channel_username}`.
  A button whose URL resolves empty is dropped, so one template serves
  both `/for_channel` and `/feed`.
- `channel_project`: channel_id, project_id, is_default.
- `project_user`: project_id, user_id.
- `bot_chats`: chat_id, telegram_user_id, name, hash, user_id, project_id
  (active project), status (`pending`, `active`, `blocked`).
- `personal_access_tokens` (Sanctum) plus a `project_id` column.

Data migration, run once:

- Create project 1 from `APP_URL`. Backfill every `short_urls` row with
  `project_id = 1`, `source = panel`, `user_id` = the admin, empty title.
- If `settings.json` has a channel, create the channel row with a template
  that reproduces the current post exactly (title, description, short url,
  channel username, "Read Article" button, optional "Read on PM Feed"
  button) and attach it to project 1 as default.
- Copy `telegraph_chats` into `bot_chats`; chats with a `user_id` become
  `active`, the rest `pending`. Old Telegraph tables are left in place
  and dropped in a later release.

## How extra domains reach the app

Point the domain's DNS at the server, add it as another domain on the same
app in Dokploy so Traefik issues a certificate and routes it to the
container, then enter the domain on the project in the panel. The app reads
the Host header: `/` resolves the project by host and redirects, `/s/{key}`
serves the link. Links created in that project store a short URL on that
domain. The panel, API and mini app answer only on the primary domain.

## Core services

- `Shortener::make(Project, User, string $url, string $source, ?string $key)`
  generates a unique key from the configured alphabet and length (DB unique
  index plus retry), stores `default_short_url` from the project domain,
  dispatches `FetchLinkTitle`. Panel, bot, API and mini app all go through
  it.
- `VisitRecorder` reproduces the old package's tracking: IP, OS and version,
  browser and version, referer, device type via whichbrowser with the same
  desktop / mobile / tablet / robot order.
- `RedirectController` on `/s/{key}`: resolve by key, record visit, 301.
- `PostComposer` renders a channel template for a link and a language,
  returning text plus keyboard. Used by the bot preview and the publish
  step.
- `ReportService` holds every analytics query, excludes
  `device_type = 'robot'`, and feeds the dashboard, bot, API and mini app.

The old key salt is gone with the package: keys are now plain random strings
checked against the unique index, so no salt is needed. Existing keys are
unaffected because they are stored, not derived.

## Phases

Each phase ends green on `php artisan test` and Pint, and is one commit.

### Phase 1: own the shortener, projects, users, domains (done)

- `Link` and `Visit` models, `Shortener`, `VisitRecorder`,
  `RedirectController`, removal of `ashallendesign/short-url`.
- Migrations and models for projects, channels, pivots, user locale; the
  data migration above.
- Root `/` on any host looks up the project by host and redirects, or 404.
- Panel: project switcher in the sidebar, project settings (domain, root
  redirect, attached channels and default), channel pages (language,
  template editor with live preview), user management and project grants
  for admins. Homepage view and settings page removed.
- Tests: existing links still redirect and still track, redirect on a
  secondary domain, root redirect, project scoping, non-admin isolation,
  data migration backfill, template rendering with missing placeholders.

### Phase 2: bot on Nutgram and projects (done)

- Replace Telegraph with Nutgram. `bot_chats` table and migration of
  existing bindings. Middleware: resolve chat, refuse `pending` with a
  "waiting for approval" reply, ignore `blocked`, ignore group chats.
- Conversations: `Short`, `ShortWithKey`, `Bulk`, `Stat`, `Report`,
  `ForChannel`, `Feed`, `Project`. `/project` lists the user's projects and
  stores the choice on the chat; new chats default to the user's first
  project.
- `/for_channel` and `/feed` pick a channel when the project has more than
  one, else the default. Post comes from `PostComposer` in the channel's
  language; the short link is on the chat's active project domain. Replies
  to the user follow the user's locale.
- Bot access page for admins: every chat that ever sent `/start`, with
  Telegram name and id, status, bound user, projects. Approve, bind, block.
  The `/auth/bot/{hash}` link stays as self-service: it creates a `pending`
  chat an admin still approves, unless the user opening it is an admin.
- Tests rewritten with Nutgram's fake bot: each conversation, the access
  gate, channel choice, template output.

### Phase 3: API (done)

- Sanctum. Keys created in the panel per project with chosen scopes, shown
  once. Rate limit per key.
- `routes/api.php` under `/api/v1`: `POST links`, `POST links/bulk`,
  `GET links`, `GET links/{key}`, `GET links/{key}/stats`, `DELETE links/{key}`,
  `GET reports/summary?days=7`. JSON resources, one error shape.
- Tests per endpoint and per scope denial.

### Phase 4: auto title and reports (done)

- `FetchLinkTitle` queued job: OpenGraph title, fallback `<title>`, fallback
  host name, 5 second timeout, Laravel Http client. Sync in tests, Redis
  worker in production. The worker now exists only for this job.
- `ReportService` replaces the duplicated queries in dashboard, `/report`,
  `/stat`. Referrer widget replaced by source and per-channel breakdown,
  since Telegram clicks carry no referer.

### Phase 5: mini app (done)

- `GET /app` on the primary domain, opened from the bot menu button.
- A `telegram` auth guard validates `initData` with Nutgram's validator
  and maps the Telegram user id to an `active` bot chat. No session cookie.
- Screens: shorten, my links, link stats, project report, compose channel
  post. Reuses Blade partials from the panel; compact layout for lists.

### Phase 6: Persian RTL

- `dir="rtl"`, `lang="fa"`, Vazirmatn font in both layouts, `locale = fa`,
  `lang/fa` for every panel string and bot reply, Jalali dates.
- Flux RTL checked page by page in the browser.

If you confirm assumption 1, this phase runs alongside phase 1 so every
new screen is written RTL from the start.

### Phase 7: docs and deploy

- README, DOCKER.md, CLAUDE.md for the new architecture: project domains
  in Dokploy, Nutgram webhook command, Sanctum env,
  mini app URL, why `/app/storage` stays a volume (logs, uploads).

## Out of scope

Scheduled reports, tags, OG images, per-post click attribution, link
expiry, QR codes, custom redirect status codes.
