<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'fa' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, maximum-scale=1">
    <title>{{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=vazirmatn:400,500,600,700|inter:400,500,600,700" rel="stylesheet">
    <link href="{{ asset('index.css') }}" rel="stylesheet">
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <script defer src="{{ asset('js/alpine-3.14.9.min.js') }}"></script>
    <style>
        :root {
            --tg-bg: var(--tg-theme-bg-color, #ffffff);
            --tg-secondary: var(--tg-theme-secondary-bg-color, #f4f4f5);
            --tg-text: var(--tg-theme-text-color, #18181b);
            --tg-hint: var(--tg-theme-hint-color, #71717a);
            --tg-link: var(--tg-theme-link-color, #2563eb);
            --tg-button: var(--tg-theme-button-color, #2563eb);
            --tg-button-text: var(--tg-theme-button-text-color, #ffffff);
        }
        html, body { background: var(--tg-bg); color: var(--tg-text); }
        .tg-card { background: var(--tg-secondary); border-radius: 14px; padding: 14px; }
        .tg-hint { color: var(--tg-hint); }
        .tg-link { color: var(--tg-link); }
        .tg-btn { background: var(--tg-button); color: var(--tg-button-text); border-radius: 12px; padding: 12px 16px; font-weight: 600; width: 100%; }
        .tg-btn:disabled { opacity: .5; }
        .tg-btn-ghost { background: transparent; color: var(--tg-link); border-radius: 12px; padding: 10px 14px; font-weight: 600; }
        .tg-input { background: var(--tg-bg); color: var(--tg-text); border: 1px solid color-mix(in srgb, var(--tg-hint) 35%, transparent); border-radius: 12px; padding: 12px 14px; width: 100%; font-size: 16px; }
        .tg-tab { flex: 1; padding: 10px 4px; font-size: 13px; font-weight: 600; color: var(--tg-hint); border-top: 2px solid transparent; }
        .tg-tab.active { color: var(--tg-link); border-top-color: var(--tg-link); }
        [x-cloak] { display: none !important; }
        .mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
    </style>
</head>
<body class="min-h-screen antialiased" x-data="app()" x-init="init()" x-cloak>
    <template x-if="state === 'loading'">
        <div class="flex min-h-screen items-center justify-center tg-hint">{{ __('miniapp.loading') }}</div>
    </template>

    <template x-if="state === 'error'">
        <div class="flex min-h-screen items-center justify-center p-8 text-center">
            <div>
                <div class="text-4xl">⚠️</div>
                <p class="mt-4" x-text="fatal"></p>
            </div>
        </div>
    </template>

    <template x-if="state === 'ready'">
        <div class="pb-20">
            <header class="flex items-center justify-between px-4 pt-4 pb-2">
                <div>
                    <div class="text-lg font-bold" x-text="me.project.name"></div>
                    <div class="tg-hint text-xs mono" dir="ltr" x-text="me.project.domain"></div>
                </div>
                <select x-show="me.projects.length > 1" class="tg-input !w-auto !py-2 text-sm" x-model="projectId" @change="switchProject()">
                    <template x-for="p in me.projects" :key="p.id"><option :value="p.id" x-text="p.name"></option></template>
                </select>
            </header>

            <div x-show="toast" x-transition class="fixed inset-x-4 top-3 z-20 rounded-xl px-4 py-3 text-sm text-center" :class="toastError ? 'bg-red-600 text-white' : 'bg-green-600 text-white'" x-text="toast"></div>

            {{-- Shorten --}}
            <section x-show="tab === 'shorten'" class="space-y-4 px-4">
                <div class="tg-card space-y-3">
                    <input class="tg-input" type="url" inputmode="url" dir="ltr" :placeholder="t.url" x-model="form.url" @keydown.enter="shorten()">
                    <input class="tg-input" type="text" dir="ltr" :placeholder="t.key_optional" x-model="form.key">
                    <button class="tg-btn" :disabled="busy || !form.url" @click="shorten()" x-text="t.shorten"></button>
                </div>

                <template x-if="created">
                    <div class="tg-card space-y-2">
                        <div class="font-medium" x-text="created.title || created.destination_url"></div>
                        <div class="mono text-sm tg-link break-all" dir="ltr" x-text="created.short_url"></div>
                        <div class="flex gap-2">
                            <button class="tg-btn-ghost" @click="copy(created.short_url)" x-text="t.copy"></button>
                            <a class="tg-btn-ghost" :href="created.short_url" target="_blank" x-text="t.open"></a>
                        </div>
                    </div>
                </template>

                <div class="tg-card space-y-3">
                    <div class="font-semibold" x-text="t.bulk"></div>
                    <div class="tg-hint text-sm" x-text="t.bulk_hint"></div>
                    <textarea class="tg-input" rows="5" dir="auto" x-model="form.text"></textarea>
                    <button class="tg-btn" :disabled="busy || !form.text" @click="bulk()" x-text="t.bulk"></button>
                    <template x-if="bulkResult">
                        <div class="space-y-2">
                            <div class="tg-hint text-sm" x-text="bulkResult.summary"></div>
                            <textarea class="tg-input" rows="5" dir="auto" readonly x-model="bulkResult.text"></textarea>
                            <button class="tg-btn-ghost" @click="copy(bulkResult.text)" x-text="t.copy"></button>
                        </div>
                    </template>
                </div>
            </section>

            {{-- Links --}}
            <section x-show="tab === 'links' && !detail" class="space-y-3 px-4">
                <input class="tg-input" type="search" dir="auto" :placeholder="t.search" x-model.debounce.400ms="search" @input="loadLinks(1)">
                <template x-if="links.length === 0 && !busy"><div class="tg-hint py-8 text-center" x-text="t.no_links"></div></template>
                <template x-for="l in links" :key="l.id">
                    <button class="tg-card w-full text-start" @click="openDetail(l.key)">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="truncate font-medium" x-text="l.title || l.destination_url"></div>
                                <div class="mono truncate text-sm tg-link" dir="ltr" x-text="'/s/' + l.key"></div>
                            </div>
                            <div class="shrink-0 text-sm tg-hint"><span x-text="num(l.clicks)"></span> <span x-text="t.clicks"></span></div>
                        </div>
                    </button>
                </template>
                <button x-show="nextPage" class="tg-btn-ghost w-full" @click="loadLinks(nextPage, true)" x-text="t.more"></button>
            </section>

            {{-- Link detail --}}
            <section x-show="tab === 'links' && detail" class="space-y-3 px-4">
                <button class="tg-btn-ghost" @click="detail = null" x-text="'‹ ' + t.back"></button>
                <template x-if="detail">
                    <div class="space-y-3">
                        <div class="tg-card space-y-2">
                            <div class="font-medium" x-text="detail.link.title || detail.link.destination_url"></div>
                            <div class="mono text-sm tg-link break-all" dir="ltr" x-text="detail.link.short_url"></div>
                            <div class="flex gap-2">
                                <button class="tg-btn-ghost" @click="copy(detail.link.short_url)" x-text="t.copy"></button>
                                <a class="tg-btn-ghost" :href="detail.link.destination_url" target="_blank" x-text="t.open"></a>
                            </div>
                        </div>
                        <div class="tg-card">
                            <div class="text-3xl font-bold" x-text="num(detail.stats.clicks_total)"></div>
                            <div class="tg-hint text-sm" x-text="t.clicks"></div>
                        </div>
                        <div class="tg-card">
                            <div class="font-semibold" x-text="t.daily"></div>
                            <div class="mt-3 flex h-24 items-end gap-1" dir="ltr">
                                <template x-for="d in detail.stats.daily" :key="d.date">
                                    <div class="flex-1 rounded-t" :style="'height:' + Math.max(4, d.clicks / maxDaily(detail.stats.daily) * 96) + 'px; background: var(--tg-link)'" :title="d.label + ': ' + d.clicks"></div>
                                </template>
                            </div>
                        </div>
                        <template x-for="block in [['browsers', t.browsers], ['devices', t.devices], ['operating_systems', t.os], ['referrers', t.referrers]]" :key="block[0]">
                            <div class="tg-card">
                                <div class="font-semibold" x-text="block[1]"></div>
                                <template x-for="row in detail.stats[block[0]]" :key="row.name || 'null'">
                                    <div class="flex justify-between py-1 text-sm">
                                        <span x-text="label(row.name)"></span><span class="tg-hint" x-text="num(row.clicks)"></span>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </template>
            </section>

            {{-- Post to channel --}}
            <section x-show="tab === 'post'" class="space-y-4 px-4">
                <template x-if="me.channels.length === 0"><div class="tg-card tg-hint" x-text="t.no_channel"></div></template>
                <template x-if="me.channels.length > 0 && !preview">
                    <div class="tg-card space-y-3">
                        <select class="tg-input" x-model="post.channel_id">
                            <template x-for="c in me.channels" :key="c.id"><option :value="c.id" x-text="c.title"></option></template>
                        </select>
                        <input class="tg-input" type="url" inputmode="url" dir="ltr" :placeholder="t.url" x-model="post.url">
                        <input class="tg-input" type="url" inputmode="url" dir="ltr" :placeholder="t.feed_url" x-model="post.feed_url">
                        <div class="tg-hint text-xs" x-text="t.preview_hint"></div>
                        <button class="tg-btn" :disabled="busy || !post.url" @click="previewPost()" x-text="t.preview"></button>
                    </div>
                </template>
                <template x-if="preview">
                    <div class="space-y-3">
                        <div class="tg-card space-y-3">
                            <div class="tg-hint text-xs" x-text="t.edit_text"></div>
                            <textarea class="tg-input" rows="8" dir="auto" x-model="preview.text"></textarea>
                            <template x-if="preview.image"><img :src="preview.image" class="w-full rounded-xl" alt=""></template>
                            <template x-for="b in preview.buttons" :key="b.url">
                                <div class="rounded-xl py-2 text-center text-sm tg-link" style="background: var(--tg-bg)" x-text="b.label"></div>
                            </template>
                        </div>
                        <button class="tg-btn" :disabled="busy" @click="publishPost()" x-text="t.publish"></button>
                        <button class="tg-btn-ghost w-full" @click="preview = null" x-text="t.discard"></button>
                    </div>
                </template>
            </section>

            {{-- Report --}}
            <section x-show="tab === 'report'" class="space-y-3 px-4">
                <template x-if="report">
                    <div class="space-y-3">
                        <div class="tg-hint text-sm" x-text="t.report_days.replace(':days', num(report.days))"></div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="tg-card"><div class="text-2xl font-bold" x-text="num(report.clicks)"></div><div class="tg-hint text-sm" x-text="t.total_clicks"></div></div>
                            <div class="tg-card"><div class="text-2xl font-bold" x-text="num(report.links_total)"></div><div class="tg-hint text-sm" x-text="t.links_total"></div></div>
                        </div>
                        <div class="tg-card">
                            <div class="font-semibold" x-text="t.top_links"></div>
                            <template x-for="l in report.top_links" :key="l.key">
                                <button class="w-full py-2 text-start" @click="tab = 'links'; openDetail(l.key)">
                                    <div class="flex justify-between gap-2">
                                        <span class="truncate" x-text="l.title || l.destination_url"></span>
                                        <span class="shrink-0 tg-hint" x-text="num(l.clicks)"></span>
                                    </div>
                                </button>
                            </template>
                        </div>
                        <div class="tg-card">
                            <div class="font-semibold" x-text="t.by_source"></div>
                            <template x-for="r in report.sources" :key="r.name">
                                <div class="flex justify-between py-1 text-sm"><span x-text="t['source_' + r.name] || r.name"></span><span class="tg-hint" x-text="num(r.clicks)"></span></div>
                            </template>
                        </div>
                        <div class="tg-card">
                            <div class="font-semibold" x-text="t.by_channel"></div>
                            <template x-for="r in report.channels" :key="r.id || 'none'">
                                <div class="flex justify-between py-1 text-sm"><span x-text="r.name || t.not_posted"></span><span class="tg-hint" x-text="num(r.clicks)"></span></div>
                            </template>
                        </div>
                    </div>
                </template>
            </section>

            <nav class="fixed inset-x-0 bottom-0 flex" style="background: var(--tg-secondary); padding-bottom: env(safe-area-inset-bottom)">
                <button class="tg-tab" :class="{active: tab === 'shorten'}" @click="go('shorten')" x-text="t.tab_shorten"></button>
                <button class="tg-tab" :class="{active: tab === 'links'}" @click="go('links')" x-text="t.tab_links"></button>
                <button class="tg-tab" :class="{active: tab === 'post'}" @click="go('post')" x-text="t.tab_post"></button>
                <button class="tg-tab" :class="{active: tab === 'report'}" @click="go('report')" x-text="t.tab_report"></button>
            </nav>
        </div>
    </template>

    <script>
        function app() {
            const tg = window.Telegram?.WebApp;
            const base = @json(url('app/api'));

            return {
                state: 'loading', fatal: '', me: null, t: {}, tab: 'shorten', busy: false,
                toast: '', toastError: false, projectId: null,
                form: { url: '', key: '', text: '' }, created: null, bulkResult: null,
                links: [], nextPage: null, search: '', detail: null,
                post: { channel_id: null, url: '', feed_url: '' }, preview: null,
                report: null,

                async init() {
                    tg?.ready(); tg?.expand();
                    if (!tg?.initData) { this.fatal = @json(__('miniapp.outside')); this.state = 'error'; return; }
                    try {
                        this.me = await this.call('GET', 'me');
                    } catch (e) { this.fatal = e.message; this.state = 'error'; return; }
                    this.t = this.me.strings;
                    this.projectId = this.me.project.id;
                    this.post.channel_id = this.me.channels[0]?.id ?? null;
                    document.documentElement.lang = this.me.user.locale;
                    document.documentElement.dir = this.me.user.locale === 'fa' ? 'rtl' : 'ltr';
                    this.state = 'ready';
                },

                async call(method, path, body) {
                    const res = await fetch(base + '/' + path, {
                        method,
                        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-Telegram-Init-Data': tg.initData },
                        body: body ? JSON.stringify(body) : undefined,
                    });
                    const json = await res.json().catch(() => ({}));
                    if (!res.ok) {
                        const fields = json.error?.fields ? Object.values(json.error.fields).flat().join(' ') : '';
                        throw new Error(fields || json.error?.message || (this.t.error ?? 'Error'));
                    }
                    return json;
                },

                async run(fn) {
                    this.busy = true;
                    try { await fn(); } catch (e) { this.notify(e.message, true); } finally { this.busy = false; }
                },

                notify(text, error = false) {
                    this.toast = text; this.toastError = error;
                    tg?.HapticFeedback?.notificationOccurred(error ? 'error' : 'success');
                    setTimeout(() => this.toast = '', 2500);
                },

                go(tab) {
                    this.tab = tab;
                    if (tab === 'links' && this.links.length === 0) this.loadLinks(1);
                    if (tab === 'report' && !this.report) this.loadReport();
                },

                async switchProject() {
                    await this.run(async () => {
                        const r = await this.call('POST', 'project', { project_id: Number(this.projectId) });
                        this.me = await this.call('GET', 'me');
                        this.links = []; this.report = null; this.detail = null; this.preview = null; this.created = null;
                        this.post.channel_id = this.me.channels[0]?.id ?? null;
                        this.notify(r.project.name);
                    });
                },

                async shorten() {
                    await this.run(async () => {
                        const r = await this.call('POST', 'links', { url: this.form.url, key: this.form.key || null });
                        this.created = r.data; this.form.url = ''; this.form.key = ''; this.links = [];
                        this.copy(r.data.short_url);
                    });
                },

                async bulk() {
                    await this.run(async () => {
                        const r = await this.call('POST', 'links/bulk', { text: this.form.text });
                        this.bulkResult = { text: r.text, summary: this.t.bulk_done.replace(':count', this.num(r.count)) };
                        this.links = [];
                    });
                },

                async loadLinks(page, append = false) {
                    await this.run(async () => {
                        const r = await this.call('GET', 'links?page=' + page + '&search=' + encodeURIComponent(this.search));
                        this.links = append ? this.links.concat(r.data) : r.data;
                        this.nextPage = r.meta.current_page < r.meta.last_page ? r.meta.current_page + 1 : null;
                    });
                },

                async openDetail(key) {
                    await this.run(async () => { this.detail = await this.call('GET', 'links/' + encodeURIComponent(key) + '/stats'); });
                },

                async loadReport() {
                    await this.run(async () => { this.report = await this.call('GET', 'report?days=7'); });
                },

                async previewPost() {
                    await this.run(async () => {
                        this.preview = await this.call('POST', 'posts/preview', { channel_id: Number(this.post.channel_id), url: this.post.url, feed_url: this.post.feed_url || null });
                    });
                },

                async publishPost() {
                    await this.run(async () => {
                        await this.call('POST', 'posts/publish', { channel_id: Number(this.post.channel_id), text: this.preview.text, short_url: this.preview.short_url, feed_url: this.preview.feed_url, image: this.preview.image });
                        this.notify(this.t.published.replace(':channel', this.preview.channel.title));
                        this.preview = null; this.post.url = ''; this.post.feed_url = ''; this.links = [];
                    });
                },

                copy(text) {
                    navigator.clipboard?.writeText(text).then(() => this.notify(this.t.copied)).catch(() => {});
                },

                num(n) {
                    const s = new Intl.NumberFormat(this.me?.user.locale === 'fa' ? 'fa-IR' : 'en-US').format(n ?? 0);
                    return s;
                },

                maxDaily(rows) { return Math.max(1, ...rows.map(r => r.clicks)); },

                label(name) {
                    if (!name) return this.t.unknown;
                    if (name === 'direct') return this.t.direct;
                    return name;
                },
            };
        }
    </script>
</body>
</html>
