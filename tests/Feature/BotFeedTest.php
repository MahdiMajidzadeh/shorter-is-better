<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Link;
use App\Models\User;
use App\Models\Channel;
use ReflectionProperty;
use App\Http\Webhooks\Handler;
use App\Http\Webhooks\State\Feed;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use App\Http\Webhooks\State\ForChannel;
use Illuminate\Support\Facades\Storage;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Models\TelegraphBot;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use DefStudio\Telegraph\Support\Testing\Fakes\TelegraphFake;

class BotFeedTest extends TestCase
{
    use RefreshDatabase;

    private TelegraphChat $chat;

    private TelegraphChat $channel;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Telegraph::fake();
        Http::fake(['*' => Http::response('<html><head><title>Fetched</title></head></html>')]);

        $user = $this->makeAdmin();
        $project = $this->makeProject();
        $this->makeChannel($project);

        $bot = TelegraphBot::factory()->create();
        $this->chat = TelegraphChat::factory()->create(['telegraph_bot_id' => $bot->id, 'chat_id' => '1001']);
        $this->chat->user_id = $user->id;
        $this->chat->save();
        $this->channel = TelegraphChat::factory()->create(['telegraph_bot_id' => $bot->id, 'chat_id' => '-1001']);
    }

    public function test_feed_command_is_advertised(): void
    {
        $this->assertArrayHasKey('feed', bot_commands());
        $this->assertArrayHasKey('for_channel', bot_commands());
    }

    public function test_disabled_and_deleted_users_lose_bot_access(): void
    {
        $handler = new class extends Handler
        {
            public function check(TelegraphChat $chat): bool
            {
                $this->chat = $chat;

                return $this->isAuthenticated();
            }
        };

        $this->assertTrue($handler->check($this->chat));

        User::first()->update(['is_active' => false]);
        $this->assertFalse($handler->check($this->chat));

        User::first()->update(['is_active' => true]);
        $this->assertTrue($handler->check($this->chat));

        User::query()->delete();
        $this->assertFalse($handler->check($this->chat));
    }

    public function test_for_channel_refuses_when_the_project_has_no_channel(): void
    {
        Channel::query()->delete();
        $this->seedStorage(ForChannel::class, 1);

        (new ForChannel($this->chat, ''))->handle();

        $this->assertSame('this project has no channel yet', $this->sentTo($this->chat)->last()['data']['text']);
        $this->assertNull($this->chat->storage()->get('state'));
    }

    public function test_feed_url_is_shortened_and_preview_is_sent(): void
    {
        $this->seedStorage(Feed::class, 3);

        (new Feed($this->chat, 'https://feed.example.com/post/1'))->handle();

        $this->assertDatabaseHas('short_urls', ['destination_url' => 'https://feed.example.com/post/1']);
        $this->assertSame(4, $this->chat->storage()->get('step'));
        $this->assertStringStartsWith('http://localhost/s/', $this->chat->storage()->get('data.feed_short'));
        $this->assertSame('bot', Link::first()->source);

        $preview = $this->sentTo($this->chat)->last();
        $actions = collect($preview['data']['reply_markup']['inline_keyboard'])
            ->flatten(1)
            ->pluck('callback_data')
            ->all();

        $this->assertSame(
            ['action:keyboard_handler;action_name:feed_confirm', 'action:keyboard_handler;action_name:feed_edit', 'action:keyboard_handler;action_name:feed_dismiss'],
            $actions
        );
    }

    public function test_invalid_feed_url_is_rejected_without_advancing(): void
    {
        $this->seedStorage(Feed::class, 3);

        (new Feed($this->chat, 'not a url'))->handle();

        $this->assertSame(0, Link::count());
        $this->assertSame(3, $this->chat->storage()->get('step'));
        $this->assertSame('feed url is not valid', $this->sentTo($this->chat)->last()['data']['text']);
    }

    public function test_confirm_publishes_to_channel_with_both_buttons_stacked(): void
    {
        $this->seedStorage(Feed::class, 4, ['feed_short' => 'https://s.test/s/feed1']);

        (new Feed($this->chat, 'feed_confirm'))->handle();

        $post = $this->sentTo($this->channel)->last();

        $this->assertSame("*Title*\n\nDesc\n\n`https://s.test/s/art1`\n@pmfeed", $post['data']['text']);
        $this->assertSame([
            [['text' => 'Read Article', 'url' => 'https://s.test/s/art1']],
            [['text' => 'Read on PM Feed', 'url' => 'https://s.test/s/feed1']],
        ], $post['data']['reply_markup']['inline_keyboard']);

        $this->assertNull($this->chat->storage()->get('state'));
        $this->assertNull($this->chat->storage()->get('data.feed_short'));
    }

    public function test_edited_text_is_published_with_both_buttons(): void
    {
        $this->seedStorage(Feed::class, 5, ['feed_short' => 'https://s.test/s/feed1']);

        (new Feed($this->chat, 'My own caption'))->handle();

        $post = $this->sentTo($this->channel)->last();

        $this->assertSame('My own caption', $post['data']['text']);
        $this->assertSame(
            ['Read Article', 'Read on PM Feed'],
            collect($post['data']['reply_markup']['inline_keyboard'])->flatten(1)->pluck('text')->all()
        );
        $this->assertNull($this->chat->storage()->get('state'));
    }

    public function test_for_channel_still_publishes_with_a_single_button(): void
    {
        $this->seedStorage(ForChannel::class, 3);

        (new ForChannel($this->chat, 'channel_confirm'))->handle();

        $post = $this->sentTo($this->channel)->last();

        $this->assertSame([
            [['text' => 'Read Article', 'url' => 'https://s.test/s/art1']],
        ], $post['data']['reply_markup']['inline_keyboard']);
    }

    private function seedStorage(string $state, int $step, array $extra = []): void
    {
        $storage = $this->chat->storage();
        $storage->set('state', $state);
        $storage->set('step', $step);
        $storage->set('data.url', 'https://example.com/article');
        $storage->set('data.url_short', 'https://s.test/s/art1');
        $storage->set('data.link_data', ['title' => 'Title', 'description' => 'Desc', 'image' => '']);
        $storage->set('data.messageText', "*Title*\n\nDesc\n\n`https://s.test/s/art1`\n@pmfeed");
        $storage->set('data.last_id', 42);

        foreach ($extra as $key => $value) {
            $storage->set('data.'.$key, $value);
        }
    }

    private function sentTo(TelegraphChat $chat): Collection
    {
        $sent = (new ReflectionProperty(TelegraphFake::class, 'sentMessages'))->getValue();

        return collect($sent)->where('chat_id', $chat->id)->values();
    }
}
