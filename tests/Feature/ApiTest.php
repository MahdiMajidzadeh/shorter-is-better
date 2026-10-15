<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Link;
use App\Models\User;
use App\Models\ApiKey;
use App\Models\Project;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Project $project;

    private Project $other;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake(['*' => Http::response('<html><head><title>Fetched title</title></head></html>')]);

        $this->user = $this->makeUser();
        $this->project = $this->makeProject(['domain' => 's.test']);
        $this->other = $this->makeProject(['name' => 'Other', 'domain' => 'o.test']);
        $this->user->projects()->attach($this->project);
    }

    private function key(array $scopes = ['links:create', 'links:read', 'links:stats', 'links:bulk', 'links:delete', 'reports:read'], ?Project $project = null, ?User $user = null): string
    {
        return ApiKey::issue($user ?? $this->user, $project ?? $this->project, 'test', $scopes)->plainTextToken;
    }

    private function api(string $token)
    {
        // The sanctum request guard caches its user for the life of the app
        // instance, which spans every request of one test. Real requests are
        // isolated processes; mimic that.
        $this->app['auth']->forgetGuards();

        return $this->withToken($token)->withHeaders(['Accept' => 'application/json']);
    }

    public function test_requests_without_a_key_are_refused_with_the_error_shape(): void
    {
        $this->getJson('api/v1/me')
            ->assertStatus(401)
            ->assertExactJson(['error' => ['code' => 'unauthenticated', 'message' => 'A valid API key is required.']]);

        $this->withToken('sib_nope')->getJson('api/v1/me')->assertStatus(401);
    }

    public function test_api_answers_only_on_the_primary_domain(): void
    {
        $token = $this->key(['links:read']);

        $this->api($token)->getJson('http://s.test/api/v1/me')->assertStatus(404);
        $this->api($token)->getJson('http://localhost/api/v1/me')->assertOk();
    }

    public function test_api_errors_are_english_whatever_the_owner_locale(): void
    {
        $this->user->update(['locale' => 'fa']);

        $this->api($this->key())->postJson('api/v1/links', ['url' => 'nope'])
            ->assertStatus(422)
            ->assertJsonPath('error.message', 'The url is not valid.');
    }

    public function test_me_describes_the_key(): void
    {
        $token = $this->key(['links:read']);
        $this->assertStringContainsString('|sib_', $token);

        $this->api($token)->getJson('api/v1/me')
            ->assertOk()
            ->assertJsonPath('key.scopes', ['links:read'])
            ->assertJsonPath('project.domain', 's.test')
            ->assertJsonPath('user.id', $this->user->id);
    }

    public function test_scopes_are_enforced(): void
    {
        $token = $this->key(['links:read']);

        $this->api($token)->postJson('api/v1/links', ['url' => 'https://example.com'])
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'forbidden');

        $this->api($token)->getJson('api/v1/reports/summary')->assertStatus(403);
        $this->api($token)->getJson('api/v1/links')->assertOk();
    }

    public function test_key_of_a_disabled_user_or_lost_project_access_is_refused(): void
    {
        $token = $this->key();

        $this->user->projects()->detach($this->project);
        $this->api($token)->getJson('api/v1/me')->assertStatus(403)->assertJsonPath('error.code', 'forbidden');

        $this->user->projects()->attach($this->project);
        $this->user->update(['is_active' => false]);
        $this->api($token)->getJson('api/v1/me')->assertStatus(401);
    }

    public function test_create_link_with_and_without_key(): void
    {
        $token = $this->key();

        $response = $this->api($token)->postJson('api/v1/links', ['url' => 'https://example.com/post'])
            ->assertStatus(201)
            ->assertJsonPath('data.source', 'api');

        // The title is fetched by a queued job; with the sync queue it is there on the next read.
        $this->api($token)->getJson('api/v1/links/'.$response->json('data.key'))->assertJsonPath('data.title', 'Fetched title');

        $this->assertStringStartsWith('http://s.test/s/', $response->json('data.short_url'));
        $this->assertSame($this->user->id, Link::first()->user_id);

        $this->api($token)->postJson('api/v1/links', ['url' => 'https://example.com/two', 'key' => 'custom'])
            ->assertStatus(201)
            ->assertJsonPath('data.short_url', 'http://s.test/s/custom');

        $this->api($token)->postJson('api/v1/links', ['url' => 'https://example.com/three', 'key' => 'custom'])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'invalid_link');

        $this->api($token)->postJson('api/v1/links', ['url' => 'not a url'])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'invalid_link');

        $this->api($token)->postJson('api/v1/links', [])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation')
            ->assertJsonStructure(['error' => ['fields' => ['url']]]);
    }

    public function test_bulk(): void
    {
        $token = $this->key();

        $response = $this->api($token)->postJson('api/v1/links/bulk', ['text' => 'a https://a.test/1 b https://b.test/2'])
            ->assertOk()
            ->assertJsonCount(2, 'links');

        $this->assertStringContainsString($response->json('links.0.short_url'), $response->json('text'));
    }

    public function test_list_show_stats_and_delete_are_scoped_to_the_key_project(): void
    {
        $token = $this->key();
        $mine = $this->makeLink($this->project, 'https://example.com/mine', ['url_key' => 'mine1', 'title' => 'Mine']);
        $theirs = $this->makeLink($this->other, 'https://example.com/theirs', ['url_key' => 'their']);
        $this->makeVisit($mine, now(), 'mobile', 'https://t.me/c');
        $this->makeVisit($mine, now(), 'robot');

        $this->api($token)->getJson('api/v1/links')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.key', 'mine1')
            ->assertJsonPath('data.0.clicks', 1)
            ->assertJsonStructure(['meta' => ['total', 'per_page']]);

        $this->api($token)->getJson('api/v1/links?search=Mine')->assertJsonCount(1, 'data');
        $this->api($token)->getJson('api/v1/links?search=zzz')->assertJsonCount(0, 'data');

        $this->api($token)->getJson('api/v1/links/their')->assertStatus(404)->assertJsonPath('error.code', 'not_found');
        $this->api($token)->getJson('api/v1/links/mine1')->assertOk()->assertJsonPath('data.title', 'Mine');

        $this->api($token)->getJson('api/v1/links/mine1/stats?days=7')
            ->assertOk()
            ->assertJsonPath('stats.clicks_total', 1)
            ->assertJsonPath('stats.devices.0', ['name' => 'mobile', 'clicks' => 1])
            ->assertJsonPath('stats.referrers.0', ['name' => 't.me', 'clicks' => 1])
            ->assertJsonCount(1, 'stats.daily');

        $this->api($token)->deleteJson('api/v1/links/their')->assertStatus(404);
        $this->api($token)->deleteJson('api/v1/links/mine1')->assertNoContent();
        $this->assertDatabaseMissing('short_urls', ['id' => $mine->id]);
        $this->assertDatabaseMissing('short_url_visits', ['short_url_id' => $mine->id]);
        $this->assertDatabaseHas('short_urls', ['id' => $theirs->id]);
    }

    public function test_summary_report(): void
    {
        $token = $this->key();
        $link = $this->makeLink($this->project, 'https://example.com/a', ['url_key' => 'aaaaa', 'source' => 'bot']);
        $this->makeVisit($link, now(), 'desktop');
        $this->makeVisit($link, now()->subDays(10), 'desktop');
        $this->makeVisit($this->makeLink($this->other), now(), 'desktop');

        $this->api($token)->getJson('api/v1/reports/summary?days=7')
            ->assertOk()
            ->assertJsonPath('clicks', 1)
            ->assertJsonPath('clicks_previous', 1)
            ->assertJsonPath('clicks_total', 2)
            ->assertJsonPath('links_total', 1)
            ->assertJsonPath('top_links.0.key', 'aaaaa')
            ->assertJsonPath('sources.0', ['name' => 'bot', 'clicks' => 1]);

        $this->api($token)->getJson('api/v1/reports/summary?days=0')->assertStatus(422);
    }

    public function test_requests_are_rate_limited_per_key(): void
    {
        $token = $this->key(['links:read']);

        for ($i = 0; $i < 120; $i++) {
            $this->api($token)->getJson('api/v1/me')->assertOk();
        }

        $this->api($token)->getJson('api/v1/me')->assertStatus(429)->assertJsonPath('error.code', 'rate_limited');
        $this->api($this->key(['links:read']))->getJson('api/v1/me')->assertOk();
    }
}
