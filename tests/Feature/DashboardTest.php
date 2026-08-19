<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use AshAllenDesign\ShortURL\Facades\ShortURL;
use AshAllenDesign\ShortURL\Models\ShortURLVisit;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $user = new User;
        $user->name = 'Test';
        $user->username = 'test';
        $user->password = Hash::make('secret-password');
        $user->is_admin = true;
        $user->is_active = true;
        $user->save();

        $this->user = $user;
    }

    public function test_dashboard_shows_empty_state_without_links(): void
    {
        $this->actingAs($this->user)
            ->get('panel')
            ->assertOk()
            ->assertSee('Create your first link');
    }

    public function test_dashboard_shows_stats_and_excludes_bots(): void
    {
        $short = ShortURL::destinationUrl('https://example.com')->make();

        $this->makeVisit($short->id, now(), 'desktop');
        $this->makeVisit($short->id, now(), 'mobile', 'https://t.me/mychannel');
        $this->makeVisit($short->id, now(), 'robot');
        $this->makeVisit($short->id, now()->subDays(8), 'desktop');

        $response = $this->actingAs($this->user)->get('panel');

        $response->assertOk()
            ->assertViewHas('clicksToday', 2)
            ->assertViewHas('clicksWeek', 2)
            ->assertViewHas('totalClicks', 3)
            ->assertViewHas('totalLinks', 1)
            ->assertSee('/s/'.$short->url_key)
            ->assertSee('t.me')
            ->assertSee('direct');
    }

    public function test_referrers_are_grouped_by_host(): void
    {
        $short = ShortURL::destinationUrl('https://example.com')->make();

        $this->makeVisit($short->id, now(), 'desktop', 'https://t.me/channel-one');
        $this->makeVisit($short->id, now(), 'desktop', 'https://t.me/channel-two');
        $this->makeVisit($short->id, now(), 'mobile', 'https://x.com/post');

        $response = $this->actingAs($this->user)->get('panel');

        $referrers = $response->viewData('referrers');

        $this->assertSame('t.me', $referrers->first()->host);
        $this->assertSame(2, $referrers->first()->views);
        $this->assertSame('x.com', $referrers->last()->host);
    }

    private function makeVisit(int $shortUrlId, Carbon $visitedAt, string $deviceType, ?string $referer = null): void
    {
        $visit = new ShortURLVisit;
        $visit->short_url_id = $shortUrlId;
        $visit->ip_address = '127.0.0.1';
        $visit->operating_system = 'OS X';
        $visit->browser = 'Firefox';
        $visit->device_type = $deviceType;
        $visit->referer_url = $referer;
        $visit->visited_at = $visitedAt;
        $visit->save();
    }
}
