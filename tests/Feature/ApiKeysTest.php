<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\ApiKey;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApiKeysTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $user;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->makeAdmin();
        $this->user = $this->makeUser();
        $this->project = $this->makeProject();
        $this->user->projects()->attach($this->project);
    }

    public function test_user_creates_a_key_for_the_current_project_and_sees_it_once(): void
    {
        $response = $this->actingAs($this->user)->post('api-keys', ['name' => 'n8n', 'scopes' => ['links:create', 'reports:read']])
            ->assertRedirect('api-keys')
            ->assertSessionHas('plain_token');

        $plain = session('plain_token');
        $this->assertStringContainsString('|sib_', $plain);

        $key = ApiKey::firstOrFail();
        $this->assertSame($this->project->id, $key->project_id);
        $this->assertSame($this->user->id, $key->tokenable_id);
        $this->assertSame(['links:create', 'reports:read'], $key->scopes());

        $this->actingAs($this->user)->get('api-keys')->assertOk()->assertSee('n8n')->assertSee($plain);
        $this->actingAs($this->user)->get('api-keys')->assertOk()->assertDontSee($plain);

        $this->actingAs($this->user)->post('api-keys', ['name' => 'x', 'scopes' => ['nope']])->assertSessionHasErrors('scopes.0');
    }

    public function test_users_only_see_their_own_keys_and_admins_see_all(): void
    {
        $mine = ApiKey::issue($this->user, $this->project, 'mine', ['links:read'])->accessToken;
        $admins = ApiKey::issue($this->admin, $this->project, 'admins', ['links:read'])->accessToken;

        $this->actingAs($this->user)->get('api-keys')->assertSee('mine')->assertDontSee('admins');
        $this->actingAs($this->admin)->get('api-keys')->assertSee('mine')->assertSee('admins');

        $this->actingAs($this->user)->post('api-keys/'.$admins->id.'/delete')->assertNotFound();
        $this->actingAs($this->user)->post('api-keys/'.$mine->id.'/delete')->assertSessionHas('msg-ok');
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $mine->id]);
    }

    public function test_deleting_a_user_removes_their_keys(): void
    {
        ApiKey::issue($this->user, $this->project, 'mine', ['links:read']);

        $this->actingAs($this->admin)->post('users/'.$this->user->id.'/delete')->assertSessionHas('msg-ok');

        $this->assertSame(0, ApiKey::count());
    }

    public function test_keys_follow_the_current_project(): void
    {
        $other = $this->makeProject(['name' => 'Other', 'domain' => 'o.test']);
        ApiKey::issue($this->admin, $other, 'other-key', ['links:read']);

        $this->actingAs($this->admin)->get('api-keys')->assertDontSee('other-key');
        $this->actingAs($this->admin)->get('panel/project/'.$other->id);
        $this->actingAs($this->admin)->get('api-keys')->assertSee('other-key');
    }
}
