<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A project is one short domain plus the channels, users and links that
 * belong to it. The domain is stored as a bare host (with port if any).
 */
class Project extends Model
{
    protected $fillable = ['name', 'domain', 'root_redirect_url'];

    public function links(): HasMany
    {
        return $this->hasMany(Link::class);
    }

    public function channels(): BelongsToMany
    {
        return $this->belongsToMany(Channel::class)->withPivot('is_default');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function defaultChannel(): ?Channel
    {
        $channels = $this->channels()->orderByDesc('channel_project.is_default')->orderBy('channels.id')->get();

        return $channels->firstWhere('pivot.is_default', true) ?? $channels->first();
    }

    public static function findByHost(?string $host): ?self
    {
        if (! $host) {
            return null;
        }

        return static::where('domain', strtolower($host))->first();
    }

    public static function domainFromUrl(string $url): string
    {
        $parts = parse_url($url);
        $host = strtolower($parts['host'] ?? $url);

        return isset($parts['port']) ? $host.':'.$parts['port'] : $host;
    }

    /**
     * Scheme follows APP_URL so a plain-http local setup keeps working;
     * everything deployed is behind TLS.
     */
    public function baseUrl(): string
    {
        $scheme = parse_url(config('app.url'), PHP_URL_SCHEME) ?: 'https';

        return $scheme.'://'.$this->domain;
    }

    public function shortUrl(string $key): string
    {
        return $this->baseUrl().'/'.config('shortener.prefix').'/'.$key;
    }

    public function setDomainAttribute(string $value): void
    {
        $this->attributes['domain'] = static::domainFromUrl(trim($value));
    }
}
