<?php

namespace App\Support;

class SanitarySubcategories
{
    /** @return array<string, array<string, mixed>> */
    public static function grouped(): array
    {
        return config('categories.sanitary_subcategories', []);
    }

    /** @return array<string, string> */
    public static function allNames(): array
    {
        $names = [];

        foreach (self::grouped() as $mainSlug => $main) {
            $names[$mainSlug] = (string) ($main['name'] ?? $mainSlug);

            foreach ($main['children'] ?? [] as $childSlug => $child) {
                $names[$childSlug] = (string) ($child['name'] ?? $childSlug);
            }
        }

        return $names;
    }

    public static function name(string $slug): ?string
    {
        $names = self::allNames();

        return isset($names[$slug]) ? (string) $names[$slug] : null;
    }

    public static function mainSlugForLeaf(string $leafSlug): ?string
    {
        foreach (self::grouped() as $mainSlug => $main) {
            if (array_key_exists($leafSlug, $main['children'] ?? [])) {
                return $mainSlug;
            }
        }

        return null;
    }

    public static function isMainSlug(string $slug): bool
    {
        return array_key_exists($slug, self::grouped());
    }

    public static function isLeafSlug(string $slug): bool
    {
        return self::mainSlugForLeaf($slug) !== null;
    }

    public static function khamatoLeafSlug(string $handle): ?string
    {
        $mapped = config("product-scraper.khamato.sanitary_collection_subcategories.{$handle}");

        return is_string($mapped) && $mapped !== '' ? $mapped : null;
    }

    public static function mahgoubLeafSlug(string $handle): ?string
    {
        $mapped = config("product-scraper.mahgoub.sanitary_collection_subcategories.{$handle}");

        return is_string($mapped) && $mapped !== '' ? $mapped : null;
    }

    /**
     * @return array<string, string> legacy_slug => leaf_slug
     */
    public static function legacyLeafMap(): array
    {
        $map = [];

        foreach (config('product-scraper.khamato.collections', []) as $handle => $name) {
            if (DepartmentSubcategories::khamatoAccessorySubcategorySlug($handle) !== null) {
                continue;
            }

            $leaf = self::khamatoLeafSlug($handle);
            if ($leaf !== null) {
                $map['khamato-'.$handle] = $leaf;
            }
        }

        foreach (config('categories.mahgoub_sanitary', []) as $handle => $name) {
            $leaf = self::mahgoubLeafSlug($handle);
            if ($leaf !== null) {
                $map['mahgoub-'.$handle] = $leaf;
                $map[$handle] = $leaf;
            }
        }

        return $map;
    }

    public static function targetLeafForSlug(string $slug): ?string
    {
        $map = self::legacyLeafMap();

        if (isset($map[$slug])) {
            return $map[$slug];
        }

        if (self::isLeafSlug($slug) || self::isMainSlug($slug)) {
            return null;
        }

        if (str_starts_with($slug, 'khamato-')) {
            return self::khamatoLeafSlug(substr($slug, 8));
        }

        if (str_starts_with($slug, 'mahgoub-')) {
            return self::mahgoubLeafSlug(substr($slug, 8));
        }

        if (str_starts_with($slug, 'sanitary-')) {
            return self::mahgoubLeafSlug($slug);
        }

        return null;
    }
}
