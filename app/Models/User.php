<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['name', 'username', 'password', 'is_admin', 'is_active', 'locale'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'is_admin'  => 'boolean',
        'is_active' => 'boolean',
    ];

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class);
    }

    /** Admins see every project; everyone else only the ones granted. */
    public function accessibleProjects(): Builder
    {
        return $this->is_admin
            ? Project::query()->orderBy('id')
            : $this->projects()->getQuery()->orderBy('projects.id');
    }

    public function accessibleProjectList(): Collection
    {
        return $this->accessibleProjects()->get();
    }

    public function canAccessProject(Project|int|null $project): bool
    {
        if (is_null($project)) {
            return false;
        }

        $id = $project instanceof Project ? $project->id : $project;

        return $this->is_admin || $this->projects()->where('projects.id', $id)->exists();
    }
}
