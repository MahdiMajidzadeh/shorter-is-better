<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->makeAdmin();
        $this->project = $this->makeProject();
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
        $short = $this->makeLink($this->project);

        $this->makeVisit($short, now(), 'desktop');
        $this->makeVisit($short, now(), 'mobile', 'https://t.me/mychannel');
        $this->makeVisit($short, now(), 'robot');
        $this->makeVisit($short, now()->subDays(8), 'desktop');

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

    public function test_visits_without_a_device_type_count_as_human(): void
    {
        $short = $this->makeLink($this->project);
        $this->makeVisit($short, now(), null);

        $this->actingAs($this->user)->get('panel')->assertViewHas('clicksToday', 1);
    }

    public function test_dashboard_is_scoped_to_the_current_project(): void
    {
        $other = $this->makeProject(['name' => 'Other', 'domain' => 'other.test']);
        $this->makeVisit($this->makeLink($this->project));
        $this->makeVisit($this->makeLink($other));
        $this->makeVisit($this->makeLink($other));

        $this->actingAs($this->user)->get('panel')->assertViewHas('totalClicks', 1);

        $this->actingAs($this->user)->get('panel/project/'.$other->id)->assertRedirect();
        $this->actingAs($this->user)->get('panel')->assertViewHas('totalClicks', 2)->assertViewHas('totalLinks', 2);
    }

    public function test_referrers_are_grouped_by_host(): void
    {
        $short = $this->makeLink($this->project);

        $this->makeVisit($short, now(), 'desktop', 'https://t.me/channel-one');
        $this->makeVisit($short, now(), 'desktop', 'https://t.me/channel-two');
        $this->makeVisit($short, now(), 'mobile', 'https://x.com/post');

        $response = $this->actingAs($this->user)->get('panel');
        $referrers = $response->viewData('referrers');

        $this->assertSame('t.me', $referrers->first()->host);
        $this->assertSame(2, $referrers->first()->views);
        $this->assertSame('x.com', $referrers->last()->host);
    }
}
