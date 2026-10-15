<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Channel;
use App\Services\PostComposer;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ChannelTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_template_reproduces_the_old_post_and_drops_the_feed_button_without_a_feed(): void
    {
        $channel = new Channel([
            'username'         => '@pmfeed',
            'language'         => 'en',
            'template_text'    => Channel::legacyTemplateText(),
            'template_buttons' => Channel::legacyTemplateButtons(),
        ]);

        $post = app(PostComposer::class)->compose($channel, [
            'title' => 'Title', 'description' => 'Desc', 'short_url' => 'https://s.test/s/art1',
        ]);

        $this->assertSame("*Title*\n\nDesc\n\n`https://s.test/s/art1`\n@pmfeed", $post['text']);
        $this->assertSame([['label' => 'Read Article', 'url' => 'https://s.test/s/art1']], $post['buttons']);

        $post = app(PostComposer::class)->compose($channel, [
            'title' => 'Title', 'description' => 'Desc', 'short_url' => 'https://s.test/s/art1', 'feed_url' => 'https://s.test/s/feed1',
        ]);

        $this->assertSame([
            ['label' => 'Read Article', 'url' => 'https://s.test/s/art1'],
            ['label' => 'Read on PM Feed', 'url' => 'https://s.test/s/feed1'],
        ], $post['buttons']);
    }

    public function test_persian_channels_get_right_to_left_marks_and_unknown_placeholders_stay(): void
    {
        $channel = new Channel([
            'language'         => 'fa',
            'template_text'    => "{title}\n\n{nope} {short_url}",
            'template_buttons' => [],
        ]);

        $post = app(PostComposer::class)->compose($channel, ['title' => 'عنوان', 'short_url' => 'https://s.test/s/a']);

        $this->assertSame("\u{200F}عنوان\n\n\u{200F}{nope} https://s.test/s/a", $post['text']);
    }

    public function test_buttons_are_parsed_from_lines(): void
    {
        $this->assertSame(
            [['label' => 'A', 'url' => '{short_url}'], ['label' => 'B', 'url' => 'https://x.test']],
            Channel::parseButtons("A | {short_url}\n\nno separator\n B|https://x.test \n | empty")
        );
    }

    public function test_preview_endpoint_renders_sample_data(): void
    {
        $admin = $this->makeAdmin();
        $this->makeProject();

        $this->actingAs($admin)->postJson('channels/preview', [
            'language' => 'en', 'username' => 'chan', 'template_text' => '{title} {channel_username}', 'template_buttons' => 'Go | {short_url}',
        ])->assertOk()->assertJson([
            'text'    => 'Sample article title @chan',
            'buttons' => [['label' => 'Go', 'url' => 'https://example.com/s/abc12']],
        ]);
    }
}
