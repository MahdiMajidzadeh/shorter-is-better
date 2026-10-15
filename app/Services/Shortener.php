<?php

namespace App\Services;

use App\Models\Link;
use App\Models\User;
use App\Models\Project;
use App\Jobs\FetchLinkTitle;
use Illuminate\Support\Collection;
use App\Exceptions\ShortenerException;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * The one place links get created. Panel, bot, API and mini app all come
 * through here so the project domain, creator, source and title are set
 * the same way everywhere.
 */
class Shortener
{
    public const URL_PATTERN = '~https?://[^\s<>"\']+~iu';

    public function make(Project $project, ?User $user, string $url, string $source, ?string $key = null): Link
    {
        $url = trim($url);

        if (! $this->isValidUrl($url)) {
            throw ShortenerException::invalidUrl();
        }

        if ($key !== null) {
            $key = trim($key);

            if (! preg_match('/^[A-Za-z0-9_-]{1,64}$/', $key)) {
                throw ShortenerException::invalidKey();
            }

            if (Link::where('url_key', $key)->exists()) {
                throw ShortenerException::keyTaken($key);
            }
        }

        $link = $this->insert($project, $user, $url, $source, $key);

        FetchLinkTitle::dispatch($link);

        return $link;
    }

    /**
     * Replace every url in a block of text with a short link. Returns the
     * rewritten text and the links that were created, in order.
     *
     * @return array{text: string, links: Collection<int, Link>}
     */
    public function bulk(Project $project, ?User $user, string $text, string $source): array
    {
        $links = collect();

        $converted = preg_replace_callback(self::URL_PATTERN, function (array $match) use ($project, $user, $source, $links) {
            $url = rtrim($match[0], '.,;:!?)');
            $tail = substr($match[0], strlen($url));

            try {
                $link = $this->make($project, $user, $url, $source);
            } catch (ShortenerException) {
                return $match[0];
            }

            $links->push($link);

            return $link->default_short_url.$tail;
        }, $text);

        return ['text' => $converted, 'links' => $links];
    }

    public function isValidUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false
            && in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true);
    }

    private function insert(Project $project, ?User $user, string $url, string $source, ?string $key): Link
    {
        $length = (int) config('shortener.key_length');
        $maxAttempts = (int) config('shortener.max_attempts');
        $attempt = 0;

        while (true) {
            $urlKey = $key ?? $this->randomKey($length);

            try {
                return Link::create([
                    'project_id'                     => $project->id,
                    'user_id'                        => $user?->id,
                    'source'                         => $source,
                    'destination_url'                => $url,
                    'url_key'                        => $urlKey,
                    'default_short_url'              => $project->shortUrl($urlKey),
                    'single_use'                     => false,
                    'forward_query_params'           => false,
                    'track_visits'                   => true,
                    'redirect_status_code'           => 301,
                    'track_ip_address'               => true,
                    'track_operating_system'         => true,
                    'track_operating_system_version' => true,
                    'track_browser'                  => true,
                    'track_browser_version'          => true,
                    'track_referer_url'              => true,
                    'track_device_type'              => true,
                    'activated_at'                   => now(),
                ]);
            } catch (UniqueConstraintViolationException $e) {
                if ($key !== null) {
                    throw ShortenerException::keyTaken($key);
                }

                if (++$attempt >= $maxAttempts) {
                    $length++;
                    $attempt = 0;
                }
            }
        }
    }

    private function randomKey(int $length): string
    {
        $alphabet = config('shortener.alphabet');
        $max = strlen($alphabet) - 1;
        $key = '';

        for ($i = 0; $i < $length; $i++) {
            $key .= $alphabet[random_int(0, $max)];
        }

        return $key;
    }
}
