<?php

namespace App\Support;

use Illuminate\Support\Facades\URL;

class AppUrl
{
    public static function root(): string
    {
        $root = rtrim((string) config('app.url'), '/');

        return $root !== '' ? $root : 'http://localhost';
    }

    /**
     * Build an absolute URL on the public site domain (never the server IP).
     */
    public static function absolute(string $path): string
    {
        $path = '/'.ltrim($path, '/');

        return static::root().$path;
    }

    /**
     * Rewrite cached or legacy absolute URLs to use APP_URL (fixes IP-based media links).
     */
    public static function normalize(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            return static::absolute($url);
        }

        $parts = parse_url($url);
        if ($parts === false || empty($parts['path'])) {
            return $url;
        }

        $normalized = static::root().$parts['path'];
        if (! empty($parts['query'])) {
            $normalized .= '?'.$parts['query'];
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>|null  $spotlight
     * @return array<string, mixed>|null
     */
    public static function normalizeComfortSpotlight(?array $spotlight): ?array
    {
        if ($spotlight === null) {
            return null;
        }

        $spotlight['image_url'] = static::normalize($spotlight['image_url'] ?? null);
        $spotlight['thumbs'] = collect($spotlight['thumbs'] ?? [])
            ->map(function (array $thumb): array {
                $thumb['image'] = static::normalize($thumb['image'] ?? null);

                return $thumb;
            })
            ->all();

        return $spotlight;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $cards
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public static function normalizeReviewCards(\Illuminate\Support\Collection $cards): \Illuminate\Support\Collection
    {
        return $cards->map(function (array $card): array {
            $card['image'] = static::normalize($card['image'] ?? null);

            return $card;
        });
    }

    public static function registerRootUrl(): void
    {
        $root = static::root();
        if ($root === 'http://localhost') {
            return;
        }

        URL::forceRootUrl($root);
        if (str_starts_with($root, 'https://')) {
            URL::forceScheme('https');
        }
    }
}
