<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A private Telegram chat that talked to the bot. Only `active` chats bound
 * to an active panel user may use it; admins approve from the panel.
 */
class BotChat extends Model
{
    public const PENDING = 'pending';

    public const ACTIVE = 'active';

    public const BLOCKED = 'blocked';

    public const STATUSES = [self::PENDING, self::ACTIVE, self::BLOCKED];

    protected $fillable = ['chat_id', 'telegram_user_id', 'name', 'username', 'hash', 'user_id', 'project_id', 'status', 'last_seen_at'];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** Usable: approved, bound to a user, and that user still active. */
    public function isUsable(): bool
    {
        return $this->status === self::ACTIVE
            && $this->user !== null
            && $this->user->is_active;
    }

    public function isBlocked(): bool
    {
        return $this->status === self::BLOCKED;
    }

    public function ensureHash(): string
    {
        if (! $this->hash) {
            $this->forceFill(['hash' => Str::random(50)])->save();
        }

        return $this->hash;
    }

    public function displayName(): string
    {
        return trim((string) $this->name) ?: ($this->username ? '@'.$this->username : (string) $this->chat_id);
    }

    /**
     * The project this chat works in. Falls back to the first project the
     * bound user can access and remembers it.
     */
    public function activeProject(): ?Project
    {
        if ($this->project && $this->user?->canAccessProject($this->project)) {
            return $this->project;
        }

        $project = $this->user?->accessibleProjects()->first();

        if ($project && $project->id !== $this->project_id) {
            $this->forceFill(['project_id' => $project->id])->save();
            $this->setRelation('project', $project);
        }

        return $project;
    }
}
