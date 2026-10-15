<?php

namespace App\Services;

use Throwable;
use Illuminate\Support\Facades\Http;

/**
 * Fetches a page's title, description and image from its Open Graph tags,
 * falling back to <title> and <meta name="description">. Never throws;
 * missing values come back as empty strings.
 */
class PageMeta
{
    /**
     * @return array{title: string, description: string, image: string}
     */
    public function fetch(string $url): array
    {
        $empty = ['title' => '', 'description' => '', 'image' => ''];

        try {
            $response = Http::timeout((int) config('shortener.fetch_timeout'))
                ->withUserAgent(config('shortener.user_agent'))
                ->accept('text/html')
                ->get($url);
        } catch (Throwable) {
            return $empty;
        }

        if (! $response->ok()) {
            return $empty;
        }

        $html = substr($response->body(), 0, 300_000);

        return [
            'title'       => $this->meta($html, 'og:title') ?? $this->titleTag($html) ?? '',
            'description' => $this->meta($html, 'og:description') ?? $this->meta($html, 'description') ?? '',
            'image'       => $this->meta($html, 'og:image') ?? '',
        ];
    }

    private function meta(string $html, string $name): ?string
    {
        $name = preg_quote($name, '~');

        $patterns = [
            '~<meta\s+[^>]*(?:property|name)=["\']'.$name.'["\'][^>]*content=["\']([^"\']*)["\']~i',
            '~<meta\s+[^>]*content=["\']([^"\']*)["\'][^>]*(?:property|name)=["\']'.$name.'["\']~i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $m) && trim($m[1]) !== '') {
                return $this->clean($m[1]);
            }
        }

        return null;
    }

    private function titleTag(string $html): ?string
    {
        if (preg_match('~<title[^>]*>(.*?)</title>~is', $html, $m) && trim($m[1]) !== '') {
            return $this->clean($m[1]);
        }

        return null;
    }

    private function clean(string $value): string
    {
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $value));
    }
}
