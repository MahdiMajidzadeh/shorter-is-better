<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Link;
use App\Models\User;
use App\Models\BotChat;
use App\Models\Project;
use Illuminate\Support\Str;
use SergiX44\Nutgram\Nutgram;
use Illuminate\Support\Facades\Http;
use SergiX44\Nutgram\Testing\FakeNutgram;
use Illuminate\Foundation\Testing\RefreshDatabase;
use SergiX44\Nutgram\Telegram\Properties\UpdateType;

class BotTest extends TestCase
{
    use RefreshDatabase;

    private const CHAT = 1001;

    private FakeNutgram $bot;

    private User $user;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake(['*' => Http::response('<html><head><meta property="og:title" content="Title"><meta property="og:description" content="Desc"></head></html>')]);

        $this->bot = app(Nutgram::class);
        $this->user = $this->makeAdmin();
        $this->project = $this->makeProject(['domain' => 's.test']);
    }

    private function approve(?User $user = null): BotChat
    {
        return BotChat::create([
            'chat_id' => self::CHAT, 'telegram_user_id' => self::CHAT, 'name' => 'Sam',
            'user_id' => ($user ?? $this->user)->id, 'status' => BotChat::ACTIVE,
        ]);
    }

    private function say(string $text, int $chat = self::CHAT, string $type = 'private'): FakeNutgram
    {
        return $this->bot->hearMessage([
            'text' => $text,
            'chat' => ['id' => $chat, 'type' => $type, 'first_name' => 'Sam'],
            'from' => ['id' => $chat, 'first_name' => 'Sam', 'is_bot' => false],
        ])->reply();
    }

    private function tap(string $data, int $chat = self::CHAT): FakeNutgram
    {
        return $this->bot->hearUpdateType(UpdateType::CALLBACK_QUERY, [
            'id'      => '77',
            'from'    => ['id' => $chat, 'first_name' => 'Sam', 'is_bot' => false],
            'data'    => $data,
            'message' => ['message_id' => 42, 'date' => 1703892479, 'chat' => ['id' => $chat, 'type' => 'private'], 'from' => ['id' => 1, 'is_bot' => true, 'first_name' => 'bot']],
        ])->reply();
    }

    public function test_unknown_chat_gets_a_linking_url_and_is_recorded_as_pending(): void
    {
        $this->say('/start');

        $chat = BotChat::where('chat_id', self::CHAT)->firstOrFail();
        $this->assertSame(BotChat::PENDING, $chat->status);
        $this->assertSame('Sam', $chat->name);
        $this->assertNotEmpty($chat->hash);

        $this->bot->assertReply('sendMessage', ['chat_id' => self::CHAT]);
        $this->assertStringContainsString('/auth/bot/'.$chat->hash, $this->bot->getRequestHistory()[0]['request']->getBody()->__toString());

        $this->say('https://example.com')->assertReplyText(__('bot_replies.not_allowed'));
        $this->assertSame(0, Link::count());
    }

    public function test_blocked_chats_and_groups_get_nothing(): void
    {
        BotChat::create(['chat_id' => self::CHAT, 'status' => BotChat::BLOCKED]);

        $this->say('/start')->assertNoReply();
        $this->say('https://example.com')->assertNoReply();
        $this->say('https://example.com', -500, 'supergroup')->assertNoReply();
        $this->assertDatabaseMissing('bot_chats', ['chat_id' => -500]);
    }

    public function test_disabled_user_loses_access(): void
    {
        $this->approve();
        $this->user->update(['is_active' => false]);

        $this->say('https://example.com')->assertReplyText(__('bot_replies.not_allowed'));
        $this->say('/start')->assertReplyText(__('bot_replies.not_allowed'));
    }

    public function test_bare_url_is_shortened_on_the_chat_project(): void
    {
        $this->approve();

        $this->say('https://example.com/post');

        $link = Link::firstOrFail();
        $this->assertSame('http://s.test/s/'.$link->url_key, $link->default_short_url);
        $this->assertSame('bot', $link->source);
        $this->assertSame($this->user->id, $link->user_id);
        $this->assertSame($this->project->id, $link->project_id);
        $this->bot->assertReplyText($link->default_short_url);

        $this->say('hello there')->assertReplyText(__('bot_replies.unknown'));
    }

    public function test_short_conversation_reprompts_on_invalid_url(): void
    {
        $this->approve();

        $this->say('/short')->assertReplyText(__('bot_replies.send_url'));
        $this->say('nope')->assertReplyText(__('The url is not valid.'));
        $this->say('https://example.com/a');

        $this->bot->assertReplyText(Link::firstOrFail()->default_short_url);
        $this->bot->assertNoConversation(self::CHAT, self::CHAT);
    }

    public function test_short_key_and_bulk_conversations(): void
    {
        $this->approve();

        $this->say('/short_key')->assertReplyText(__('bot_replies.send_url'));
        $this->say('https://example.com/k')->assertReplyText(__('bot_replies.send_key'));
        $this->say('my-key')->assertReplyText('http://s.test/s/my-key');

        $this->say('/bulk')->assertReplyText(__('bot_replies.send_bulk'));
        $this->say('see https://a.test/x and https://b.test/y');

        $links = Link::where('source', 'bot')->where('url_key', '!=', 'my-key')->orderBy('id')->get();
        $this->assertCount(2, $links);
        $this->bot->assertReplyText('see '.$links[0]->default_short_url.' and '.$links[1]->default_short_url, 0);
        $this->bot->assertReplyText(__('bot_replies.bulk_done', ['count' => 2]), 1);
    }

    public function test_cancel_ends_a_conversation(): void
    {
        $this->approve();

        $this->say('/short');
        $this->bot->assertActiveConversation(self::CHAT, self::CHAT);
        $this->say('/cancel')->assertReplyText(__('bot_replies.cancelled'));
        $this->bot->assertNoConversation(self::CHAT, self::CHAT);
    }

    public function test_project_command_switches_the_active_project(): void
    {
        $other = $this->makeProject(['name' => 'Other', 'domain' => 'o.test']);
        $regular = $this->makeUser();
        $regular->projects()->attach($this->project);
        $chat = $this->approve($regular);

        $this->say('/project');
        $this->bot->assertReply('sendMessage', ['reply_markup' => ['inline_keyboard' => [[['text' => '✅ Main · s.test', 'callback_data' => 'project:'.$this->project->id]]]]]);

        $this->tap('project:'.$other->id);
        $this->bot->assertReply('answerCallbackQuery', ['text' => __('bot_replies.project_not_allowed')]);
        $this->assertSame($this->project->id, $chat->fresh()->activeProject()->id);

        $regular->projects()->attach($other);
        $this->tap('project:'.$other->id);
        $this->bot->assertReply('editMessageText', ['text' => __('bot_replies.project_switched', ['project' => 'Other', 'domain' => 'o.test'])], 1);
        $this->assertSame($other->id, $chat->fresh()->project_id);

        $this->say('https://example.com/z');
        $this->assertSame('http://o.test/s/'.Link::firstOrFail()->url_key, Link::firstOrFail()->default_short_url);
    }

    public function test_for_channel_previews_and_publishes_from_the_template(): void
    {
        $this->approve();
        $channel = $this->makeChannel($this->project, ['chat_id' => '-1001']);

        $this->say('/for_channel')->assertReplyText(__('bot_replies.send_url'));
        $this->say('https://example.com/article');

        $short = Link::firstOrFail()->default_short_url;
        $this->bot->assertReplyMessage([
            'text'         => "*Title*\n\nDesc\n\n`{$short}`\n@pmfeed",
            'parse_mode'   => 'Markdown',
            'reply_markup' => ['inline_keyboard' => [[
                ['text' => __('bot_replies.confirm'), 'callback_data' => 'post:confirm'],
                ['text' => __('bot_replies.edit'), 'callback_data' => 'post:edit'],
                ['text' => __('bot_replies.dismiss'), 'callback_data' => 'post:dismiss'],
            ]]],
        ]);

        $this->tap('post:confirm');
        $this->bot->assertReply('answerCallbackQuery', null, 0);
        $this->bot->assertReply('editMessageReplyMarkup', ['message_id' => 42], 1);
        $this->bot->assertReply('sendMessage', [
            'chat_id'      => -1001,
            'text'         => "*Title*\n\nDesc\n\n`{$short}`\n@pmfeed",
            'reply_markup' => ['inline_keyboard' => [[['text' => 'Read Article', 'url' => $short]]]],
        ], 2);
        $this->bot->assertReplyText(__('bot_replies.published', ['channel' => $channel->title]), 3);
        $this->bot->assertNoConversation(self::CHAT, self::CHAT);
        $this->assertSame($channel->id, Link::firstOrFail()->channel_id);
    }

    public function test_a_refused_channel_post_is_reported_not_claimed(): void
    {
        $this->approve();
        $channel = $this->makeChannel($this->project, ['chat_id' => '-1001']);

        $this->say('/for_channel');
        $this->say('https://example.com/article');

        $this->bot->willReceive(true)->willReceive(true)->willReceive(['description' => 'chat not found'], ok: false);
        $this->tap('post:confirm');

        $texts = collect($this->bot->getRequestHistory())
            ->map(fn (array $pair) => (string) $pair['request']->getBody())
            ->filter(fn (string $body) => str_contains($body, '"text"'))
            ->values();

        $this->assertTrue($texts->contains(fn (string $body) => str_contains($body, json_encode(__('bot_replies.publish_failed', ['channel' => $channel->title]), JSON_UNESCAPED_UNICODE))));
        $this->assertFalse($texts->contains(fn (string $body) => str_contains($body, json_encode(__('bot_replies.published', ['channel' => $channel->title]), JSON_UNESCAPED_UNICODE))));
        $this->assertTrue($texts->contains(fn (string $body) => str_contains($body, Str::before(__('bot_replies.telegram_error'), ':error'))));
        $this->assertFalse($texts->contains(fn (string $body) => str_contains($body, 'telegram_error')));
        $this->bot->assertNoConversation(self::CHAT, self::CHAT);
    }

    public function test_feed_adds_the_second_button_and_edit_replaces_the_text(): void
    {
        $this->approve();
        $this->makeChannel($this->project, ['chat_id' => '-1001']);

        $this->say('/feed');
        $this->say('https://example.com/article')->assertReplyText(__('bot_replies.send_feed_url'));
        $this->say('not a url')->assertReplyText(__('bot_replies.invalid_feed_url'));
        $this->say('https://feed.example.com/1');

        [$article, $feed] = Link::orderBy('id')->get()->map->default_short_url->all();

        $this->tap('post:edit');
        $this->bot->assertReplyText(__('bot_replies.send_replacement'), 2);

        $this->say('My own caption');
        $this->bot->assertReply('sendMessage', [
            'chat_id'      => -1001,
            'text'         => 'My own caption',
            'reply_markup' => ['inline_keyboard' => [
                [['text' => 'Read Article', 'url' => $article]],
                [['text' => 'Read on PM Feed', 'url' => $feed]],
            ]],
        ], 0);
    }

    public function test_for_channel_asks_which_channel_when_the_project_has_several(): void
    {
        $this->approve();
        $this->makeChannel($this->project, ['chat_id' => '-1001', 'title' => 'One']);
        $second = $this->makeChannel($this->project, ['chat_id' => '-1002', 'title' => 'Two', 'username' => '@two', 'language' => 'fa'], false);

        $this->say('/for_channel')->assertReply('sendMessage', ['reply_markup' => ['inline_keyboard' => [
            [['text' => 'One', 'callback_data' => 'channel:'.$second->id - 1]],
            [['text' => 'Two', 'callback_data' => 'channel:'.$second->id]],
        ]]]);

        $this->say('https://example.com/x')->assertReplyText(__('bot_replies.use_buttons'));

        $this->tap('channel:'.$second->id);
        $this->bot->assertReplyText(__('bot_replies.send_url'), 2);

        $this->say('https://example.com/article');
        $this->tap('post:confirm');
        $this->bot->assertReply('sendMessage', ['chat_id' => -1002, 'text' => "\u{200F}*Title*\n\n\u{200F}Desc\n\n\u{200F}`".Link::firstOrFail()->default_short_url."`\n\u{200F}@two"], 2);
    }

    public function test_for_channel_without_a_channel_and_dismiss(): void
    {
        $this->approve();

        $this->say('/for_channel')->assertReplyText(__('bot_replies.no_channel'));
        $this->bot->assertNoConversation(self::CHAT, self::CHAT);

        $this->makeChannel($this->project);
        $this->say('/for_channel');
        $this->say('https://example.com/article');
        $this->tap('post:dismiss');
        $this->bot->assertReplyText(__('bot_replies.dismissed'), 2);
        $this->bot->assertNoConversation(self::CHAT, self::CHAT);
    }

    public function test_stat_and_report(): void
    {
        $this->approve();
        $link = $this->makeLink($this->project, 'https://example.com/p', ['url_key' => 'abc12', 'title' => 'Post']);
        $this->makeVisit($link, now(), 'mobile');
        $this->makeVisit($link, now(), 'robot');
        $this->makeLink($this->makeProject(['name' => 'X', 'domain' => 'x.test']), 'https://x', ['url_key' => 'other']);

        $this->say('/stat')->assertReplyText(__('bot_replies.send_short_url'));
        $this->say('http://s.test/s/other')->assertReplyText(__('bot_replies.link_not_found'));
        $this->say('http://s.test/s/abc12');
        $body = $this->bot->getRequestHistory()[0]['request']->getBody()->__toString();
        $this->assertStringContainsString('Post', $body);
        $this->assertStringContainsString(__('bot_replies.clicks', ['count' => 1]), $body);

        $this->say('/report');
        $body = $this->bot->getRequestHistory()[0]['request']->getBody()->__toString();
        $this->assertStringContainsString('Post', $body);
        $this->assertStringContainsString(__('bot_replies.clicks', ['count' => 1]), $body);
    }

    public function test_replies_follow_the_users_locale(): void
    {
        $this->user->update(['locale' => 'fa']);
        $this->approve();

        $this->say('/short')->assertReplyText('لینک را بفرست.');
    }
}
