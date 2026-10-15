<?php

namespace App\Support;

use App\Models\Link;
use App\Models\User;
use App\Models\Channel;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

/**
 * Imports the pre-project data layout: a single implicit domain (APP_URL)
 * and one channel kept in storage/settings.json. Idempotent, and a no-op on
 * a database that has neither users nor links.
 */
class LegacyImport
{
    public function __construct(private ?string $settingsPath = null)
    {
        $this->settingsPath ??= storage_path('settings.json');
    }

    public function run(): ?Project
    {
        $hasData = DB::table('users')->exists() || DB::table('short_urls')->exists();

        if (! $hasData || Project::exists()) {
            return null;
        }

        $project = Project::create([
            'name'   => config('app.name', 'Shorter'),
            'domain' => Project::domainFromUrl(config('app.url')),
        ]);

        $admin = User::where('is_admin', true)->orderBy('id')->first();

        Link::whereNull('project_id')->update([
            'project_id' => $project->id,
            'user_id'    => $admin?->id,
            'source'     => Link::SOURCE_PANEL,
        ]);

        $this->importChannel($project);

        return $project;
    }

    private function importChannel(Project $project): void
    {
        $settings = $this->readSettings();
        $chatId = $settings['channel']['id'] ?? null;

        if (! $chatId) {
            return;
        }

        $username = $settings['channel']['username'] ?? null;

        $channel = Channel::create([
            'title'            => $username ?: $chatId,
            'chat_id'          => $chatId,
            'username'         => $username,
            'language'         => 'en',
            'template_text'    => Channel::legacyTemplateText(),
            'template_buttons' => Channel::legacyTemplateButtons(),
        ]);

        $project->channels()->attach($channel->id, ['is_default' => true]);
    }

    private function readSettings(): array
    {
        if (! is_file($this->settingsPath)) {
            return [];
        }

        return json_decode((string) file_get_contents($this->settingsPath), true) ?: [];
    }
}
