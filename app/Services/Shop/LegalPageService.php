<?php

namespace App\Services\Shop;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use InvalidArgumentException;

class LegalPageService
{
    public const PAGE_KEYS = ['privacy', 'terms', 'returns', 'shipping'];

    public function homepageStrip(): array
    {
        $defaults = config('legal-pages.homepage_strip', []);
        $stored = Setting::getValue('legal_pages_homepage_strip');

        if (! is_array($stored)) {
            return $defaults;
        }

        return array_merge($defaults, $stored);
    }

    /**
     * @return list<array{key: string, title: string, url: string, icon: string, excerpt: string}>
     */
    public function homepageLinks(): array
    {
        $strip = $this->homepageStrip();
        if (! ($strip['is_active'] ?? true)) {
            return [];
        }

        return collect(self::PAGE_KEYS)
            ->map(function (string $key): ?array {
                $page = $this->resolve($key);
                if (! ($page['is_active'] ?? true)) {
                    return null;
                }

                $excerpt = collect($page['sections'] ?? [])
                    ->flatMap(fn (array $section): array => $section['paragraphs'] ?? [])
                    ->first();

                return [
                    'key' => $key,
                    'title' => (string) ($page['page_title'] ?? ''),
                    'url' => route($this->routeName($key)),
                    'icon' => (string) ($page['icon'] ?? 'document'),
                    'excerpt' => $excerpt ? (string) $excerpt : '',
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    public function resolve(string $key): array
    {
        if (! in_array($key, self::PAGE_KEYS, true)) {
            throw new InvalidArgumentException("Unknown legal page: {$key}");
        }

        $page = Cache::remember("shop.legal_page.v4.{$key}", 3600, function () use ($key): array {
            $defaults = config("legal-pages.pages.{$key}", []);
            $stored = Setting::getValue('legal_pages_content');

            if (! is_array($stored) || ! is_array($stored[$key] ?? null)) {
                return $this->normalizePage($defaults, $key);
            }

            return $this->normalizePage($this->mergePageData($defaults, $stored[$key]), $key);
        });

        $page['sections'] = $this->normalizeSections($page['sections'] ?? []);

        return $page;
    }

    public function resolveBySlug(string $slug): ?array
    {
        foreach (self::PAGE_KEYS as $key) {
            $page = $this->resolve($key);
            if (($page['slug'] ?? '') === $slug) {
                return $page + ['_key' => $key];
            }
        }

        return null;
    }

    public function routeName(string $key): string
    {
        return match ($key) {
            'privacy' => 'shop.legal.privacy',
            'terms' => 'shop.legal.terms',
            'returns' => 'shop.legal.returns',
            'shipping' => 'shop.legal.shipping',
            default => throw new InvalidArgumentException("Unknown legal page: {$key}"),
        };
    }

    public function keyFromRouteName(string $routeName): ?string
    {
        return match ($routeName) {
            'shop.legal.privacy' => 'privacy',
            'shop.legal.terms' => 'terms',
            'shop.legal.returns' => 'returns',
            'shop.legal.shipping' => 'shipping',
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $defaults
     * @param  array<string, mixed>  $stored
     * @return array<string, mixed>
     */
    protected function mergePageData(array $defaults, array $stored): array
    {
        $merged = array_merge($defaults, $stored);

        $storedSections = $stored['sections'] ?? null;
        if (! is_array($storedSections) || $storedSections === []) {
            $merged['sections'] = $defaults['sections'] ?? [];
        }

        return $merged;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalizePage(array $data, string $key): array
    {
        $defaults = config("legal-pages.pages.{$key}", []);

        $sections = $this->normalizeSections($data['sections'] ?? $defaults['sections'] ?? []);

        return [
            'key' => $key,
            'slug' => (string) ($data['slug'] ?? $defaults['slug'] ?? $key),
            'icon' => (string) ($data['icon'] ?? $defaults['icon'] ?? 'document'),
            'is_active' => (bool) ($data['is_active'] ?? $defaults['is_active'] ?? true),
            'page_title' => (string) ($data['page_title'] ?? $defaults['page_title'] ?? ''),
            'seo_title' => (string) ($data['seo_title'] ?? $defaults['seo_title'] ?? ''),
            'seo_description' => (string) ($data['seo_description'] ?? $defaults['seo_description'] ?? ''),
            'sections' => $sections,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $sections
     * @return array<int, array<string, mixed>>
     */
    public function normalizeSections(array $sections): array
    {
        return collect($sections)
            ->map(function (array $section, int $index): array {
                $title = (string) ($section['title'] ?? '');
                $id = filled($section['id'] ?? null)
                    ? (string) $section['id']
                    : ((filled($title) ? Str::slug($title, '-', 'ar') : '') ?: 'section-'.($index + 1));

                return [
                    'id' => Str::slug($id, '-', 'ar') ?: 'section-'.($index + 1),
                    'title' => $title,
                    'paragraphs' => collect($section['paragraphs'] ?? [])
                        ->map(fn ($p): string => trim(is_array($p) ? (string) ($p['text'] ?? '') : (string) $p))
                        ->filter(fn (string $p): bool => filled($p))
                        ->values()
                        ->all(),
                    'bullets' => collect($section['bullets'] ?? [])
                        ->map(fn ($b): string => trim(is_array($b) ? (string) ($b['text'] ?? '') : (string) $b))
                        ->filter(fn (string $b): bool => filled($b))
                        ->values()
                        ->all(),
                ];
            })
            ->filter(fn (array $section): bool => filled($section['title']) || $section['paragraphs'] !== [] || $section['bullets'] !== [])
            ->values()
            ->all();
    }

    public function clearCache(): void
    {
        foreach (self::PAGE_KEYS as $key) {
            Cache::forget("shop.legal_page.{$key}");
            Cache::forget("shop.legal_page.v2.{$key}");
            Cache::forget("shop.legal_page.v3.{$key}");
            Cache::forget("shop.legal_page.v4.{$key}");
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function allPagesForAdmin(): array
    {
        $out = [];
        foreach (self::PAGE_KEYS as $key) {
            $defaults = config("legal-pages.pages.{$key}", []);
            $stored = Setting::getValue('legal_pages_content');
            $merged = is_array($stored[$key] ?? null)
                ? array_merge($defaults, $stored[$key])
                : $defaults;
            $out[$key] = $this->normalizePage($merged, $key);
        }

        return $out;
    }
}
