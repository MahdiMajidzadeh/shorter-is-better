<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Link;
use App\Models\User;
use App\Models\BotChat;
use App\Models\Project;
use SergiX44\Nutgram\Nutgram;
use Illuminate\Support\Facades\Http;
use SergiX44\Nutgram\Testing\FakeNutgram;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MiniAppTest extends TestCase
{
    use RefreshDatabase;

    private const TG_USER = 7001;

    private FakeNutgram $bot;

    private User $user;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake(['*' => Http::response('<html><head><meta property="og:title" content="Title"><meta property="og:description" content="Desc"><meta property="og:image" content="https://img.test/a.jpg"></head></html>')]);

        // The fake bot signs with its own constant whatever the config says;
        // the middleware only needs to see that a token is configured.
        config(['nutgram.token' => 'configured-in-test']);

        $this->bot = app(Nutgram::class);
        $this->user = $this->makeUser(['locale' => 'fa']);
        $this->project = $this->makeProject(['domain' => 's.test']);
        $this->user->projects()->attach($this->project);
    }

    private function approve(?User $user = null, string $status = BotChat::ACTIVE): BotChat
    {
        return BotChat::create([
            'chat_id' => self::TG_USER, 'telegram_user_id' => self::TG_USER, 'name' => 'Sam',
            'user_id' => ($user ?? $this->user)->id, 'status' => $status,
        ]);
    }

    private function initData(int $userId = self::TG_USER, ?int $authDate = null): string
    {
        return $this->bot->generateWebAppData([
            'query_id'  => 'AAE',
            'user'      => json_encode(['id' => $userId, 'first_name' => 'Sam', 'language_code' => 'fa']),
            'auth_date' => $authDate ?? time(),
        ]);
    }

    private function app(?string $initData = null)
    {
        $this->app['auth']->forgetGuards();

        return $this->withHeaders(['X-Telegram-Init-Data' => $initData ?? $this->initData(), 'Accept' => 'application/json']);
    }

    public function test_shell_renders_on_the_primary_domain_only(): void
    {
        $this->get('app')->assertOk()->assertSee('telegram-web-app.js', false);
        $this->get('http://s.test/app')->assertNotFound();
    }

    public function test_mini_app_refuses_to_run_without_a_bot_token(): void
    {
        $this->approve();
        config(['nutgram.token' => null]);

        $this->app()->getJson('app/api/me')->assertStatus(503);
    }

    public function test_requests_need_valid_fresh_init_data_from_an_approved_chat(): void
    {
        $this->getJson('app/api/me')->assertStatus(401)->assertJsonPath('error.code', 'http_401');

        $this->app('query_id=AAE&user=%7B%22id%22%3A7001%7D&auth_date=1&hash=deadbeef')->getJson('app/api/me')->assertStatus(401);

        $this->app($this->initData(authDate: time() - 90000))->getJson('app/api/me')->assertStatus(401);

        $this->app()->getJson('app/api/me')->assertStatus(403);

        $this->approve(status: BotChat::PENDING);
        $this->app()->getJson('app/api/me')->assertStatus(403);
    }

    public function test_me_returns_the_chat_project_channels_and_strings_in_the_user_locale(): void
    {
        $this->approve();
        $channel = $this->makeChannel($this->project, ['title' => 'PM Feed']);

        $this->app()->getJson('app/api/me')
            ->assertOk()
            ->assertJsonPath('user.locale', 'fa')
            ->assertJsonPath('project.domain', 's.test')
            ->assertJsonPath('channels.0', ['id' => $channel->id, 'title' => 'PM Feed', 'language' => 'en', 'is_default' => true])
            ->assertJsonPath('strings.tab_shorten', 'کوتاه‌کن');
    }

    public function test_shorten_bulk_list_and_stats(): void
    {
        $this->approve();

        $created = $this->app()->postJson('app/api/links', ['url' => 'https://example.com/post', 'key' => 'mini1'])
            ->assertStatus(201)
            ->assertJsonPath('data.short_url', 'http://s.test/s/mini1')
            ->assertJsonPath('data.source', 'miniapp');

        $this->assertSame($this->user->id, Link::firstOrFail()->user_id);

        $this->app()->postJson('app/api/links', ['url' => 'nope'])->assertStatus(422)->assertJsonPath('error.code', 'invalid_link');

        $this->app()->postJson('app/api/links/bulk', ['text' => 'x https://a.test/1 y'])->assertOk()->assertJsonPath('count', 1);

        $this->makeVisit(Link::firstOrFail(), now(), 'mobile');

        $this->app()->getJson('app/api/links?search=mini1')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.clicks', 1);
        $this->app()->getJson('app/api/links/mini1/stats')->assertOk()
            ->assertJsonPath('stats.clicks_total', 1)
            ->assertJsonPath('stats.daily.0.label', local_date(now()));
        $this->app()->getJson('app/api/links/nope/stats')->assertStatus(404);
    }

    public function test_switch_project_and_report(): void
    {
        $chat = $this->approve();
        $other = $this->makeProject(['name' => 'Other', 'domain' => 'o.test']);

        $this->app()->postJson('app/api/project', ['project_id' => $other->id])->assertStatus(403);

        $this->user->projects()->attach($other);
        $this->app()->postJson('app/api/project', ['project_id' => $other->id])->assertOk()->assertJsonPath('project.domain', 'o.test');
        $this->assertSame($other->id, $chat->fresh()->project_id);

        $link = $this->makeLink($other, 'https://example.com/o', ['source' => 'bot']);
        $this->makeVisit($link);

        $this->app()->getJson('app/api/report?days=7')->assertOk()
            ->assertJsonPath('clicks', 1)
            ->assertJsonPath('sources.0.name', 'bot')
            ->assertJsonPath('daily.0.label', local_date(now()));
    }

    public function test_preview_creates_the_links_and_publish_posts_with_template_buttons(): void
    {
        $this->approve();
        $channel = $this->makeChannel($this->project, ['chat_id' => '-1001']);

        $preview = $this->app()->postJson('app/api/posts/preview', [
            'channel_id' => $channel->id, 'url' => 'https://example.com/article', 'feed_url' => 'https://feed.test/1',
        ])->assertOk();

        [$article, $feed] = Link::orderBy('id')->get();
        $this->assertSame([$channel->id, $channel->id], [$article->channel_id, $feed->channel_id]);
        $this->assertSame('miniapp', $article->source);

        $preview->assertJsonPath('short_url', $article->default_short_url)
            ->assertJsonPath('text', "*Title*\n\nDesc\n\n`{$article->default_short_url}`\n@pmfeed")
            ->assertJsonPath('image', 'https://img.test/a.jpg')
            ->assertJsonCount(2, 'buttons');

        $this->app()->postJson('app/api/posts/publish', [
            'channel_id' => $channel->id, 'text' => 'Edited caption', 'short_url' => $article->default_short_url,
            'feed_url'   => $feed->default_short_url, 'image' => 'https://img.test/a.jpg',
        ])->assertOk()->assertJsonPath('ok', true);

        // sendPhoto goes out as multipart, so compare the raw body.
        $history = $this->bot->getRequestHistory();
        $this->assertSame('sendPhoto', $history[0]['request']->getUri()->getPath());
        $body = (string) $history[0]['request']->getBody();

        foreach (['-1001', 'Edited caption', 'https://img.test/a.jpg', 'Read Article', 'Read on PM Feed',
            json_encode($article->default_short_url), json_encode($feed->default_short_url)] as $needle) {
            $this->assertStringContainsString($needle, $body);
        }

        $this->app()->postJson('app/api/posts/publish', ['channel_id' => 999, 'text' => 'x', 'short_url' => $article->default_short_url])->assertStatus(404);
        $this->app()->postJson('app/api/posts/publish', ['channel_id' => $channel->id, 'text' => 'x', 'short_url' => 'https://evil.test/s/x'])->assertStatus(422);
    }
}
