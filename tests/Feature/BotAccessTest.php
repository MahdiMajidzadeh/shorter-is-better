<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\BotChat;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BotAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->makeAdmin();
        $this->project = $this->makeProject();
    }

    public function test_hash_link_binds_regular_users_as_pending_and_admins_as_active(): void
    {
        $chat = BotChat::create(['chat_id' => 1, 'hash' => 'h1']);
        $regular = $this->makeUser();

        $this->actingAs($regular)->get('auth/bot/h1')->assertRedirect('panel');
        $this->assertSame([$regular->id, BotChat::PENDING], [$chat->fresh()->user_id, $chat->fresh()->status]);

        $this->actingAs($this->admin)->get('auth/bot/h1')->assertRedirect('panel');
        $this->assertSame([$this->admin->id, BotChat::ACTIVE], [$chat->fresh()->user_id, $chat->fresh()->status]);

        $this->actingAs($this->admin)->get('auth/bot/nope')->assertNotFound();
    }

    public function test_hash_link_survives_logging_in_first(): void
    {
        $chat = BotChat::create(['chat_id' => 1, 'hash' => 'h1']);
        $regular = $this->makeUser(['username' => 'sam']);

        $this->get('auth/bot/h1')->assertRedirect(route('login'));
        $this->post('auth', ['username' => 'sam', 'password' => 'secret-password'])->assertRedirect('auth/bot/h1');

        $this->get('auth/bot/h1')->assertRedirect('panel');
        $this->assertSame([$regular->id, BotChat::PENDING], [$chat->fresh()->user_id, $chat->fresh()->status]);
    }

    public function test_login_without_an_intended_url_lands_on_the_panel(): void
    {
        $this->makeUser(['username' => 'sam']);

        $this->post('auth', ['username' => 'sam', 'password' => 'secret-password'])->assertRedirect('panel');
    }

    public function test_admin_approves_blocks_and_removes_chats(): void
    {
        $chat = BotChat::create(['chat_id' => 1, 'name' => 'Sam', 'telegram_user_id' => 1]);
        $regular = $this->makeUser();

        $this->actingAs($this->admin)->get('bot/chats')->assertOk()->assertSee('Sam');
        $this->actingAs($regular)->get('bot/chats')->assertForbidden();

        $this->actingAs($this->admin)->post('bot/chats/'.$chat->id, ['status' => 'active', 'user_id' => ''])
            ->assertSessionHas('msg-error');
        $this->assertSame(BotChat::PENDING, $chat->fresh()->status);

        $this->actingAs($this->admin)->post('bot/chats/'.$chat->id, ['status' => 'active', 'user_id' => $regular->id])
            ->assertSessionHas('msg-ok');
        $this->assertTrue($chat->fresh()->isUsable());
        $this->assertNull($chat->fresh()->activeProject(), 'the user has no project yet');

        $this->actingAs($this->admin)->post('bot/chats/'.$chat->id, ['status' => 'blocked', 'user_id' => $regular->id]);
        $this->assertTrue($chat->fresh()->isBlocked());

        $this->actingAs($this->admin)->post('bot/chats/'.$chat->id.'/delete');
        $this->assertDatabaseMissing('bot_chats', ['id' => $chat->id]);
    }

    public function test_deleting_a_user_unlinks_their_chat(): void
    {
        $regular = $this->makeUser();
        $chat = BotChat::create(['chat_id' => 1, 'user_id' => $regular->id, 'status' => BotChat::ACTIVE]);

        $this->actingAs($this->admin)->post('users/'.$regular->id.'/delete')->assertSessionHas('msg-ok');

        $this->assertSame([null, BotChat::PENDING], [$chat->fresh()->user_id, $chat->fresh()->status]);
    }

    public function test_settings_page_explains_the_missing_token_and_register_refuses(): void
    {
        config(['nutgram.token' => null]);

        $this->actingAs($this->admin)->get('settings')->assertOk()->assertSee('TELEGRAM_TOKEN');
        $this->actingAs($this->admin)->post('settings/bot/register')->assertSessionHas('msg-error');
    }
}
