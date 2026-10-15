<?php

namespace App\Models;

use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An API key: a Sanctum token owned by a user, bound to one project, with
 * scopes stored as Sanctum abilities. The plain token is shown once.
 */
class ApiKey extends PersonalAccessToken
{
    public const SCOPES = [
        'links:create' => 'Create links',
        'links:read'   => 'List and read links',
        'links:stats'  => 'Read link statistics',
        'links:bulk'   => 'Bulk-shorten text',
        'links:delete' => 'Delete links',
        'reports:read' => 'Read project reports',
    ];

    protected $table = 'personal_access_tokens';

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @param  list<string>  $scopes
     */
    public static function issue(User $user, Project $project, string $name, array $scopes): NewAccessToken
    {
        $scopes = array_values(array_intersect($scopes, array_keys(self::SCOPES)));

        $token = $user->createToken($name, $scopes ?: ['links:read']);
        $token->accessToken->forceFill(['project_id' => $project->id])->save();

        return $token;
    }

    public function scopes(): array
    {
        return array_values(array_filter($this->abilities ?? [], fn ($a) => $a !== '*'));
    }
}
