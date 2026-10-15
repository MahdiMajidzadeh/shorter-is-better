<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Visit;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RedirectTest extends TestCase
{
    use RefreshDatabase;

    private const FIREFOX = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:109.0) Gecko/20100101 Firefox/115.0';

    private const GOOGLEBOT = 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';

    private const IPHONE = 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.0 Mobile/15E148 Safari/604.1';

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = $this->makeProject();
    }

    public function test_short_link_redirects_and_records_a_visit(): void
    {
        $short = $this->makeLink($this->project, 'https://example.com/a');

        $this->withHeaders(['User-Agent' => self::FIREFOX, 'Referer' => 'https://t.me/c'])
            ->get('/s/'.$short->url_key)
            ->assertRedirect('https://example.com/a')
            ->assertStatus(301);

        $visit = Visit::first();
        $this->assertSame($short->id, $visit->short_url_id);
        $this->assertSame('desktop', $visit->device_type);
        $this->assertSame('Firefox', $visit->browser);
        $this->assertSame('OS X', $visit->operating_system);
        $this->assertSame('https://t.me/c', $visit->referer_url);
    }

    public function test_robots_are_classified_like_before(): void
    {
        $short = $this->makeLink($this->project);

        $this->withHeaders(['User-Agent' => self::GOOGLEBOT])->get('/s/'.$short->url_key)->assertRedirect();
        $this->withHeaders(['User-Agent' => self::IPHONE])->get('/s/'.$short->url_key)->assertRedirect();

        $this->assertSame(['robot', 'mobile'], Visit::orderBy('id')->pluck('device_type')->all());
        $this->assertSame(1, Visit::human()->count());
    }

    public function test_links_created_before_projects_still_redirect(): void
    {
        $short = $this->makeLink($this->project, 'https://example.com/old', [
            'project_id' => null, 'url_key' => 'old12', 'default_short_url' => 'http://localhost/s/old12',
        ]);

        $this->get('/s/old12')->assertRedirect('https://example.com/old');
    }

    public function test_links_resolve_on_any_project_domain(): void
    {
        $other = $this->makeProject(['name' => 'Other', 'domain' => 'other.test']);
        $short = $this->makeLink($other, 'https://example.com/b');

        $this->assertSame('http://other.test/s/'.$short->url_key, $short->default_short_url);

        $this->get('http://other.test/s/'.$short->url_key)->assertRedirect('https://example.com/b');
        $this->get('http://localhost/s/'.$short->url_key)->assertRedirect('https://example.com/b');
    }

    public function test_unknown_key_is_404(): void
    {
        $this->get('/s/nope1')->assertNotFound();
    }

    public function test_root_of_a_project_domain_redirects_to_its_configured_url(): void
    {
        $this->makeProject(['name' => 'Other', 'domain' => 'other.test', 'root_redirect_url' => 'https://t.me/mychannel']);

        $this->get('http://other.test/')->assertRedirect('https://t.me/mychannel');
    }

    public function test_root_of_the_primary_domain_goes_to_the_panel(): void
    {
        $this->get('http://localhost/')->assertRedirect('panel');
    }

    public function test_root_of_an_unknown_domain_is_404(): void
    {
        $this->get('http://unknown.test/')->assertNotFound();
    }
}
