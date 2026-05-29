<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class AppUrl
{
    public static function root(): string
    {
        foreach (static::rootCandidates() as $candidate) {
            if (static::isPublicUrl($candidate)) {
                return rtrim($candidate, '/');
            }
        }

        $fallback = rtrim((string) config('app.url'), '/');

        return $fallback !== '' ? $fallback : 'http://localhost';
    }

    /**
     * @return list<string>
     */
    protected static function rootCandidates(): array
    {
        $candidates = [
            (string) config('app.public_url'),
            (string) config('app.url'),
            (string) config('app.asset_url'),
            (string) env('ASSET_URL', ''),
        ];

        $request = static::request();
        if ($request !== null) {
            $candidates[] = $request->getSchemeAndHttpHost();
        }

        return array_values(array_filter($candidates, fn (string $value): bool => $value !== ''));
    }

    protected static function request(): ?Request
    {
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return null;
        }

        try {
            return request();
        } catch (\Throwable) {
            return null;
        }
    }

    public static function isPublicUrl(?string $url): bool
    {
        if (blank($url)) {
            return false;
        }

        $parts = parse_url($url);
        $host = strtolower((string) ($parts['host'] ?? ''));

        if ($host === '') {
            return false;
        }

        return ! in_array($host, ['localhost', '127.0.0.1', '::1'], true);
    }

    /**
     * Build an absolute URL on the public site domain (never localhost when a public host exists).
     */
    public static function absolute(string $path): string
    {
        $path = '/'.ltrim($path, '/');

        return static::root().$path;
    }

    /**
     * Rewrite cached or legacy absolute URLs to use the public site domain.
     */
    public static function normalize(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            return static::absolute($url);
        }

        if (! static::shouldRewriteHost($url)) {
            return $url;
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

    public static function shouldRewriteHost(string $url): bool
    {
        if (! static::isPublicUrl(static::root())) {
            return false;
        }

        $parts = parse_url($url);
        $host = strtolower((string) ($parts['host'] ?? ''));

        if ($host === '') {
            return true;
        }

        if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            return true;
        }

        return filter_var($host, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Re-normalize URLs inside cached page payloads (fixes legacy localhost links).
     */
    public static function rewriteCachedValue(mixed $value): mixed
    {
        if (is_string($value)) {
            return static::rewriteIfUrl($value);
        }

        if (! is_array($value)) {
            return $value;
        }

        $rewritten = [];
        foreach ($value as $key => $item) {
            $rewritten[$key] = static::rewriteCachedValue($item);
        }

        return $rewritten;
    }

    protected static function rewriteIfUrl(string $value): string
    {
        if (preg_match('#^https?://#i', $value)
            || str_starts_with($value, '/media/')
            || str_starts_with($value, '/storage/')) {
            return static::normalize($value) ?? $value;
        }

        return $value;
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
        if (! static::isPublicUrl($root)) {
            return;
        }

        URL::forceRootUrl($root);
        if (str_starts_with($root, 'https://')) {
            URL::forceScheme('https');
        }
    }
}
