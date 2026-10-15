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
            ->assertViewHas('summary', fn (array $s) => $s['clicks_today'] === 2 && $s['clicks'] === 2 && $s['clicks_total'] === 3 && $s['links_total'] === 1)
            ->assertSee('/s/'.$short->url_key);
    }

    public function test_visits_without_a_device_type_count_as_human(): void
    {
        $short = $this->makeLink($this->project);
        $this->makeVisit($short, now(), null);

        $this->actingAs($this->user)->get('panel')->assertViewHas('summary', fn (array $s) => $s['clicks_today'] === 1);
    }

    public function test_dashboard_is_scoped_to_the_current_project(): void
    {
        $other = $this->makeProject(['name' => 'Other', 'domain' => 'other.test']);
        $this->makeVisit($this->makeLink($this->project));
        $this->makeVisit($this->makeLink($other));
        $this->makeVisit($this->makeLink($other));

        $this->actingAs($this->user)->get('panel')->assertViewHas('summary', fn (array $s) => $s['clicks_total'] === 1);

        $this->actingAs($this->user)->get('panel/project/'.$other->id)->assertRedirect();
        $this->actingAs($this->user)->get('panel')->assertViewHas('summary', fn (array $s) => $s['clicks_total'] === 2 && $s['links_total'] === 2);
    }

    public function test_dashboard_breaks_clicks_down_by_source_and_channel(): void
    {
        $channel = $this->makeChannel($this->project, ['title' => 'PM Feed']);
        $posted = $this->makeLink($this->project, 'https://example.com/a', ['source' => 'bot', 'channel_id' => $channel->id]);
        $plain = $this->makeLink($this->project, 'https://example.com/b', ['source' => 'api']);

        $this->makeVisit($posted, now(), 'desktop', 'https://t.me/c');
        $this->makeVisit($posted, now(), 'mobile');
        $this->makeVisit($plain, now(), 'desktop');

        $summary = $this->actingAs($this->user)->get('panel')->assertOk()
            ->assertSee('PM Feed')
            ->assertSee('Not posted to a channel')
            ->viewData('summary');

        $this->assertSame([['name' => 'bot', 'clicks' => 2], ['name' => 'api', 'clicks' => 1]], $summary['sources']);
        $this->assertSame([['id' => $channel->id, 'name' => 'PM Feed', 'clicks' => 2], ['id' => null, 'name' => null, 'clicks' => 1]], $summary['channels']);
        $this->assertSame([['name' => 'direct', 'clicks' => 2], ['name' => 't.me', 'clicks' => 1]], $summary['referrers']);
    }

    public function test_link_detail_uses_the_shared_stats(): void
    {
        $short = $this->makeLink($this->project);
        $this->makeVisit($short, now(), 'mobile', 'https://t.me/c');
        $this->makeVisit($short, now()->subDays(40), 'desktop');

        $this->actingAs($this->user)->get('links/'.$short->url_key)->assertOk()
            ->assertViewHas('stats', fn (array $s) => $s['clicks_total'] === 2 && $s['clicks'] === 1 && $s['devices'][0]['name'] === 'mobile')
            ->assertSee('t.me');
    }
}
