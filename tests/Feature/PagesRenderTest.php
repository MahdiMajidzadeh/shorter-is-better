<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PagesRenderTest extends TestCase
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

    public function test_login_page_renders(): void
    {
        $this->get('/auth')->assertOk();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/panel')->assertRedirect('auth');
    }

    public function test_disabled_users_cannot_sign_in(): void
    {
        $this->makeUser(['username' => 'off', 'is_active' => false]);

        $this->post('/auth', ['username' => 'off', 'password' => 'secret-password'])
            ->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_panel_pages_render(): void
    {
        $short = $this->makeLink($this->project, 'https://example.com/some/long/path');
        $this->makeVisit($short);
        $channel = $this->makeChannel($this->project);
        $other = $this->makeUser();

        foreach ([
            'panel',
            'links',
            'links/create',
            'links/bulk',
            'links/logs',
            'api-keys',
            'links/'.$short->url_key,
            'projects',
            'projects/create',
            'projects/'.$this->project->id.'/edit',
            'channels',
            'channels/create',
            'channels/'.$channel->id.'/edit',
            'users',
            'users/create',
            'users/'.$other->id.'/edit',
            'settings',
            'bot/chats',
        ] as $page) {
            $this->actingAs($this->user)->get($page)->assertOk();
        }
    }

    public function test_panel_renders_in_persian_for_persian_users(): void
    {
        $this->user->update(['locale' => 'fa']);

        $this->actingAs($this->user)->get('panel')
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee('داشبورد');
    }

    public function test_admin_pages_are_hidden_from_regular_users(): void
    {
        $regular = $this->makeUser();
        $regular->projects()->attach($this->project);

        foreach (['projects', 'channels', 'users', 'settings'] as $page) {
            $this->actingAs($regular)->get($page)->assertForbidden();
        }

        $this->actingAs($regular)->get('panel')->assertOk()->assertDontSee('Administration');
    }

    public function test_error_pages_render_in_the_user_language(): void
    {
        $this->user->update(['locale' => 'fa']);

        $this->actingAs($this->user)->get('links/does-not-exist')->assertNotFound()->assertSee('صفحه پیدا نشد');
        $this->get('http://unknown.test/')->assertNotFound()->assertSee('صفحه پیدا نشد');
    }

    public function test_validation_messages_are_persian_with_field_names(): void
    {
        $this->user->update(['locale' => 'fa']);

        $this->actingAs($this->user)->from('links/create')->post('links/create', ['url' => ''])
            ->assertRedirect('links/create')
            ->assertSessionHasErrors(['url' => 'فیلد آدرس الزامی است.']);
    }

    public function test_link_page_replaces_the_daily_chart_with_a_message_when_the_month_is_quiet(): void
    {
        $short = $this->makeLink($this->project);
        $this->makeVisit($short, now()->subDays(40));

        $this->actingAs($this->user)->get('links/'.$short->url_key)
            ->assertOk()
            ->assertSee('No clicks in the last 30 days.')
            ->assertDontSee('view-chart')
            ->assertDontSee('apexcharts');
    }

    public function test_link_page_draws_the_daily_chart_when_the_month_has_clicks(): void
    {
        $short = $this->makeLink($this->project);
        $this->makeVisit($short, now()->subDays(3));

        $this->actingAs($this->user)->get('links/'.$short->url_key)
            ->assertOk()
            ->assertSee('view-chart')
            ->assertSee('apexcharts')
            ->assertDontSee('No clicks in the last 30 days.');
    }

    public function test_unknown_short_key_returns_404(): void
    {
        $this->actingAs($this->user)->get('links/does-not-exist')->assertNotFound();
    }

    public function test_admin_without_projects_is_sent_to_create_one(): void
    {
        $this->project->delete();

        $this->actingAs($this->user)->get('panel')->assertRedirect('projects/create');
        $this->actingAs($this->user)->get('projects/create')->assertOk();
    }
}
