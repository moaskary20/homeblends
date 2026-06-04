<?php

namespace App\Support;

class HomeApplianceCategories
{
    /** @return array<string, string> */
    public static function canonicalNames(): array
    {
        return config('categories.home_appliances', []);
    }

    public static function canonicalName(string $slug): ?string
    {
        $names = self::canonicalNames();

        return isset($names[$slug]) ? (string) $names[$slug] : null;
    }

    /**
     * Legacy per-vendor category slugs created before unified storefront categories.
     *
     * @return array<string, string> old_slug => canonical_slug
     */
    public static function legacySlugMap(): array
    {
        $map = [];

        foreach (['sallab', 'raya', 'shaheen'] as $vendor) {
            $params = config("product-scraper.{$vendor}.collection_params", []);

            foreach ($params as $handle => $config) {
                $canonical = (string) ($config['category_slug'] ?? $handle);
                $map["{$vendor}-{$handle}"] = $canonical;
            }
        }

        return $map;
    }
}
