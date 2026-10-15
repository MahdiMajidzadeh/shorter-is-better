<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A short link. Lives on the `short_urls` table the previous shortener
 * package created, so every historical row and its visits carry over.
 */
class Link extends Model
{
    public const SOURCE_PANEL = 'panel';

    public const SOURCE_BOT = 'bot';

    public const SOURCE_API = 'api';

    public const SOURCE_MINIAPP = 'miniapp';

    public const SOURCES = [self::SOURCE_PANEL, self::SOURCE_BOT, self::SOURCE_API, self::SOURCE_MINIAPP];

    protected $table = 'short_urls';

    protected $guarded = [];

    protected $casts = [
        'single_use'           => 'boolean',
        'forward_query_params' => 'boolean',
        'track_visits'         => 'boolean',
        'activated_at'         => 'datetime',
        'deactivated_at'       => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** The channel this link was posted to by the bot, if any. */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class, 'short_url_id');
    }

    public function scopeForProject(Builder $query, Project|int $project): Builder
    {
        return $query->where('project_id', $project instanceof Project ? $project->id : $project);
    }

    public static function findByKey(string $key): ?self
    {
        return static::where('url_key', $key)->first();
    }

    public function displayTitle(): string
    {
        return $this->title ?: (parse_url($this->destination_url, PHP_URL_HOST) ?: $this->destination_url);
    }
}
