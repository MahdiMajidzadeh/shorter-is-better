<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use AshAllenDesign\ShortURL\Facades\ShortURL;
use AshAllenDesign\ShortURL\Models\ShortURLVisit;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PagesRenderTest extends TestCase
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

    public function test_landing_page_renders(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_login_page_renders(): void
    {
        $this->get('/auth')->assertOk();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/panel')->assertRedirect('auth');
    }

    public function test_panel_pages_render(): void
    {
        $short = ShortURL::destinationUrl('https://example.com/some/long/path')->make();

        $visit = new ShortURLVisit;
        $visit->short_url_id = $short->id;
        $visit->ip_address = '127.0.0.1';
        $visit->operating_system = 'OS X';
        $visit->operating_system_version = '10.15';
        $visit->browser = 'Firefox';
        $visit->browser_version = '100';
        $visit->device_type = 'desktop';
        $visit->visited_at = now();
        $visit->save();

        foreach ([
            'panel',
            'links',
            'links/create',
            'links/bulk',
            'links/logs',
            'links/'.$short->url_key,
            'settings',
            'settings/bots/create',
        ] as $page) {
            $this->actingAs($this->user)->get($page)->assertOk();
        }
    }

    public function test_unknown_short_key_returns_404(): void
    {
        $this->actingAs($this->user)->get('links/does-not-exist')->assertNotFound();
    }

    public function test_short_link_redirects(): void
    {
        $short = ShortURL::destinationUrl('https://example.com')->make();

        $this->get('/s/'.$short->url_key)
            ->assertRedirect('https://example.com');
    }
}
