<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Link;
use App\Models\User;
use App\Models\Project;
use App\Services\Shortener;
use Illuminate\Support\Facades\Http;
use App\Exceptions\ShortenerException;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ShortenerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Project $project;

    private Shortener $shortener;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            'down.test/*' => Http::response('', 500),
            '*'           => Http::response('<html><head><meta property="og:title" content="Og &amp; Title"><title>Plain</title></head></html>'),
        ]);

        $this->user = $this->makeAdmin();
        $this->project = $this->makeProject(['domain' => 's.example.com']);
        $this->shortener = app(Shortener::class);
    }

    public function test_link_is_created_on_the_project_domain_with_title_and_source(): void
    {
        $link = $this->shortener->make($this->project, $this->user, 'https://example.com/post', Link::SOURCE_BOT);

        $this->assertSame(5, strlen($link->url_key));
        $this->assertSame('http://s.example.com/s/'.$link->url_key, $link->default_short_url);
        $this->assertSame($this->project->id, $link->project_id);
        $this->assertSame($this->user->id, $link->user_id);
        $this->assertSame('bot', $link->source);
        $this->assertSame('Og & Title', $link->fresh()->title);
    }

    public function test_custom_key_is_honoured_and_must_be_unique(): void
    {
        $link = $this->shortener->make($this->project, $this->user, 'https://example.com', Link::SOURCE_PANEL, 'my-key');
        $this->assertSame('my-key', $link->url_key);

        $this->expectException(ShortenerException::class);
        $this->shortener->make($this->project, $this->user, 'https://example.com/other', Link::SOURCE_PANEL, 'my-key');
    }

    public function test_invalid_urls_and_keys_are_rejected(): void
    {
        foreach (['not a url', 'ftp://example.com', 'javascript:alert(1)'] as $url) {
            try {
                $this->shortener->make($this->project, $this->user, $url, Link::SOURCE_PANEL);
                $this->fail("$url was accepted");
            } catch (ShortenerException) {
            }
        }

        $this->expectException(ShortenerException::class);
        $this->shortener->make($this->project, $this->user, 'https://example.com', Link::SOURCE_PANEL, 'bad key!');
    }

    public function test_bulk_replaces_every_url_and_keeps_the_text(): void
    {
        $result = $this->shortener->bulk($this->project, $this->user, 'see https://a.test/x and https://b.test/y. done', Link::SOURCE_PANEL);

        $this->assertCount(2, $result['links']);
        $this->assertSame(
            'see '.$result['links'][0]->default_short_url.' and '.$result['links'][1]->default_short_url.'. done',
            $result['text']
        );
        $this->assertSame('https://b.test/y', $result['links'][1]->destination_url);
    }

    public function test_panel_create_and_bulk_use_the_current_project(): void
    {
        $this->actingAs($this->user)->post('links/create', ['url' => 'https://example.com/p', 'key' => 'panel1'])
            ->assertRedirect('links/panel1');
        $this->assertDatabaseHas('short_urls', ['url_key' => 'panel1', 'project_id' => $this->project->id, 'source' => 'panel', 'user_id' => $this->user->id]);

        $this->actingAs($this->user)->post('links/bulk', ['text' => 'go https://example.com/q now'])
            ->assertRedirect('links/bulk')
            ->assertSessionHas('converted_count', 1);
    }

    public function test_panel_reports_a_taken_key(): void
    {
        $this->makeLink($this->project, 'https://example.com', ['url_key' => 'taken']);

        $this->actingAs($this->user)->from('links/create')->post('links/create', ['url' => 'https://example.com/p', 'key' => 'taken'])
            ->assertRedirect('links/create')
            ->assertSessionHas('msg-error');
    }

    public function test_title_fetch_failure_leaves_title_empty(): void
    {
        $link = $this->shortener->make($this->project, $this->user, 'https://down.test/x', Link::SOURCE_API);

        $this->assertNull($link->fresh()->title);
        $this->assertSame('down.test', $link->displayTitle());
    }
}
