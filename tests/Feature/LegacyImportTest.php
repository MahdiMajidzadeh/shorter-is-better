<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Link;
use App\Models\Project;
use App\Support\LegacyImport;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LegacyImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_fresh_database_is_left_alone(): void
    {
        $this->assertNull((new LegacyImport)->run());
        $this->assertSame(0, Project::count());
    }

    public function test_existing_install_gets_project_one_and_its_channel(): void
    {
        $admin = $this->makeAdmin();
        DB::table('short_urls')->insert([
            'destination_url' => 'https://example.com', 'url_key' => 'old01', 'default_short_url' => 'http://localhost/s/old01',
            'single_use'      => false, 'track_visits' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $settings = tempnam(sys_get_temp_dir(), 'settings');
        file_put_contents($settings, json_encode(['channel' => ['has' => 'on', 'username' => 'mychan', 'id' => '-1001837340107']]));

        $project = (new LegacyImport($settings))->run();

        $this->assertSame('localhost', $project->domain);
        $this->assertSame($project->id, Link::where('url_key', 'old01')->first()->project_id);
        $this->assertSame($admin->id, Link::where('url_key', 'old01')->first()->user_id);

        $channel = $project->defaultChannel();
        $this->assertSame('-1001837340107', $channel->chat_id);
        $this->assertSame('@mychan', $channel->username);
        $this->assertSame(['Read Article', 'Read on PM Feed'], array_column($channel->template_buttons, 'label'));

        $this->assertNull((new LegacyImport($settings))->run(), 'runs only once');
    }
}
