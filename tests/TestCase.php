<?php

namespace Tests;

use App\Models\Link;
use App\Models\User;
use App\Models\Visit;
use App\Models\Channel;
use App\Models\Project;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function makeUser(array $attributes = []): User
    {
        return User::create($attributes + [
            'name'      => 'Test',
            'username'  => 'user'.uniqid(),
            'password'  => Hash::make('secret-password'),
            'is_admin'  => false,
            'is_active' => true,
            'locale'    => 'en',
        ]);
    }

    protected function makeAdmin(array $attributes = []): User
    {
        return $this->makeUser($attributes + ['username' => 'admin', 'is_admin' => true]);
    }

    protected function makeProject(array $attributes = []): Project
    {
        return Project::create($attributes + [
            'name'   => 'Main',
            'domain' => 'localhost',
        ]);
    }

    protected function makeChannel(Project $project, array $attributes = [], bool $default = true): Channel
    {
        $channel = Channel::create($attributes + [
            'title'            => 'Channel',
            'chat_id'          => '-1001',
            'username'         => '@pmfeed',
            'language'         => 'en',
            'template_text'    => Channel::legacyTemplateText(),
            'template_buttons' => Channel::legacyTemplateButtons(),
        ]);

        $project->channels()->attach($channel->id, ['is_default' => $default]);

        return $channel;
    }

    protected function makeLink(Project $project, string $url = 'https://example.com', array $attributes = []): Link
    {
        $key = $attributes['url_key'] ?? substr(md5(uniqid()), 0, 6);

        return Link::create($attributes + [
            'project_id'                     => $project->id,
            'destination_url'                => $url,
            'url_key'                        => $key,
            'default_short_url'              => $project->shortUrl($key),
            'single_use'                     => false,
            'track_visits'                   => true,
            'track_ip_address'               => true,
            'track_operating_system'         => true,
            'track_operating_system_version' => true,
            'track_browser'                  => true,
            'track_browser_version'          => true,
            'track_referer_url'              => true,
            'track_device_type'              => true,
        ]);
    }

    protected function makeVisit(Link $link, ?Carbon $visitedAt = null, ?string $deviceType = 'desktop', ?string $referer = null): Visit
    {
        return Visit::create([
            'short_url_id'     => $link->id,
            'ip_address'       => '127.0.0.1',
            'operating_system' => 'OS X',
            'browser'          => 'Firefox',
            'device_type'      => $deviceType,
            'referer_url'      => $referer,
            'visited_at'       => $visitedAt ?? now(),
        ]);
    }
}
