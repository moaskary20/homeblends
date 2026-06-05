<?php

namespace App\Support;

class MahgoubCategories
{
    /** @return array<string, string> */
    public static function canonicalNames(): array
    {
        return array_merge(
            config('categories.mahgoub_ceramics', []),
            config('categories.mahgoub_sanitary', [])
        );
    }

    public static function canonicalName(string $slug): ?string
    {
        $names = self::canonicalNames();

        return isset($names[$slug]) ? (string) $names[$slug] : null;
    }

    /**
     * @return array<string, string> old_slug => canonical_slug
     */
    public static function legacySlugMap(): array
    {
        $map = [];
        $params = config('product-scraper.mahgoub.collection_params', []);

        foreach ($params as $handle => $config) {
            $canonical = (string) ($config['category_slug'] ?? $handle);
            $map["mahgoub-{$handle}"] = $canonical;
        }

        return $map;
    }

    public static function parentSlugForHandle(string $handle): string
    {
        $parents = config('product-scraper.mahgoub.collection_parents', []);

        return (string) ($parents[$handle] ?? 'ceramics');
    }
}
