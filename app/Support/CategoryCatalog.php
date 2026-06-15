<?php

namespace App\Support;

class CategoryCatalog
{
    /** @return array<string, string> */
    public static function imageSources(): array
    {
        return config('categories.category_image_sources', []);
    }

    public static function imageSource(string $slug): ?string
    {
        $sources = self::imageSources();

        return isset($sources[$slug]) ? (string) $sources[$slug] : null;
    }

    /** @return array<int, string> */
    public static function configuredSlugs(): array
    {
        $slugs = [];

        foreach (config('categories.main_departments', []) as $department) {
            $slugs[] = (string) ($department['slug'] ?? '');
        }

        foreach (config('categories.department_subcategories', []) as $subcategories) {
            $slugs = array_merge($slugs, array_keys($subcategories));
        }

        foreach (config('categories.sanitary_subcategories', []) as $mainSlug => $main) {
            $slugs[] = $mainSlug;
            $slugs = array_merge($slugs, array_keys($main['children'] ?? []));
        }

        return array_values(array_unique(array_filter($slugs)));
    }

    public static function isConfiguredSlug(string $slug): bool
    {
        return in_array($slug, self::configuredSlugs(), true);
    }
}
