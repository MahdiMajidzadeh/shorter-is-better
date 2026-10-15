<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One click on a short link, on the `short_url_visits` table.
 */
class Visit extends Model
{
    public const DEVICE_DESKTOP = 'desktop';

    public const DEVICE_MOBILE = 'mobile';

    public const DEVICE_TABLET = 'tablet';

    public const DEVICE_ROBOT = 'robot';

    protected $table = 'short_url_visits';

    protected $guarded = [];

    protected $casts = [
        'visited_at' => 'datetime',
    ];

    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class, 'short_url_id');
    }

    /** Visits by people: everything not classified as a robot. */
    public function scopeHuman(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->whereNull('short_url_visits.device_type')
            ->orWhere('short_url_visits.device_type', '!=', self::DEVICE_ROBOT));
    }

    public function scopeForProject(Builder $query, Project|int $project): Builder
    {
        $id = $project instanceof Project ? $project->id : $project;

        return $query->whereIn('short_url_id', Link::forProject($id)->select('id'));
    }
}
