<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProjectAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $user;

    private Project $mine;

    private Project $theirs;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->makeAdmin();
        $this->user = $this->makeUser();
        $this->mine = $this->makeProject(['name' => 'Mine', 'domain' => 'mine.test']);
        $this->theirs = $this->makeProject(['name' => 'Theirs', 'domain' => 'theirs.test']);
        $this->user->projects()->attach($this->mine);
    }

    public function test_regular_user_only_sees_granted_projects(): void
    {
        $this->assertSame(['Mine'], $this->user->accessibleProjectList()->pluck('name')->all());
        $this->assertSame(['Mine', 'Theirs'], $this->admin->accessibleProjectList()->pluck('name')->all());
    }

    public function test_regular_user_cannot_switch_to_or_open_links_of_other_projects(): void
    {
        $foreign = $this->makeLink($this->theirs);
        $own = $this->makeLink($this->mine);

        $this->actingAs($this->user)->get('panel/project/'.$this->theirs->id)->assertForbidden();
        $this->actingAs($this->user)->get('links/'.$foreign->url_key)->assertNotFound();
        $this->actingAs($this->user)->post('links/delete/'.$foreign->id)->assertNotFound();
        $this->assertDatabaseHas('short_urls', ['id' => $foreign->id]);

        $this->actingAs($this->user)->get('links')->assertOk()->assertSee($own->url_key)->assertDontSee($foreign->url_key);
        $this->actingAs($this->user)->get('links/'.$own->url_key)->assertOk();
    }

    public function test_user_without_any_project_gets_a_clear_message(): void
    {
        $lonely = $this->makeUser();

        $this->actingAs($lonely)->get('links/create')->assertOk()->assertSee('You have no project yet');
        $this->actingAs($this->admin)->get('panel')->assertOk();
    }

    public function test_panel_and_login_answer_only_on_the_primary_domain(): void
    {
        $this->actingAs($this->admin)->get('http://mine.test/panel')->assertNotFound();
        $this->get('http://mine.test/auth')->assertNotFound();
        $this->actingAs($this->admin)->get('http://localhost/panel')->assertOk();
        $this->actingAs($this->admin)->get('http://localhost:8000/panel')->assertOk();
    }

    public function test_admin_manages_projects_channels_and_grants(): void
    {
        $channel = $this->makeChannel($this->mine, [], false);

        $this->actingAs($this->admin)->post('projects', [
            'name'              => 'New',
            'domain'            => 'New.Test',
            'root_redirect_url' => 'https://t.me/new',
            'channels'          => [$channel->id],
            'default_channel'   => $channel->id,
            'users'             => [$this->user->id],
        ])->assertRedirect('projects');

        $project = Project::where('domain', 'new.test')->firstOrFail();
        $this->assertTrue($project->defaultChannel()->is($channel));
        $this->assertTrue($this->user->fresh()->canAccessProject($project));

        $this->actingAs($this->admin)->post('projects', ['name' => 'Dup', 'domain' => 'new.test'])
            ->assertSessionHasErrors('domain');
    }

    public function test_project_with_links_cannot_be_deleted(): void
    {
        $this->makeLink($this->mine);

        $this->actingAs($this->admin)->post('projects/'.$this->mine->id.'/delete')->assertSessionHas('msg-error');
        $this->assertDatabaseHas('projects', ['id' => $this->mine->id]);

        $this->actingAs($this->admin)->post('projects/'.$this->theirs->id.'/delete')->assertSessionHas('msg-ok');
        $this->assertDatabaseMissing('projects', ['id' => $this->theirs->id]);
    }

    public function test_admin_creates_and_edits_users(): void
    {
        $this->actingAs($this->admin)->post('users', [
            'name'     => 'Sam', 'username' => 'sam', 'password' => 'long-enough', 'is_admin' => 0, 'is_active' => 1, 'locale' => 'fa',
            'projects' => [$this->theirs->id],
        ])->assertRedirect('users');

        $sam = User::where('username', 'sam')->firstOrFail();
        $this->assertTrue($sam->canAccessProject($this->theirs));
        $this->assertFalse($sam->canAccessProject($this->mine));

        $this->actingAs($this->admin)->post('users/'.$this->admin->id.'/delete')->assertSessionHas('msg-error');
    }
}
