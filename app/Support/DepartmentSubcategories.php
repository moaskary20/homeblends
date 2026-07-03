<?php

namespace App\Support;

class DepartmentSubcategories
{
    /** @return array<string, array<string, array{name: string, sort_order?: int, description?: string, image?: string}>> */
    public static function grouped(): array
    {
        return config('categories.department_subcategories', []);
    }

    /** @return array<string, string> */
    public static function canonicalNames(string $departmentSlug): array
    {
        $group = self::grouped()[$departmentSlug] ?? [];

        return collect($group)
            ->mapWithKeys(fn (array $row, string $slug): array => [$slug => (string) ($row['name'] ?? $slug)])
            ->all();
    }

    /** @return array<int, string> */
    public static function canonicalSlugs(string $departmentSlug): array
    {
        return array_keys(self::grouped()[$departmentSlug] ?? []);
    }

    public static function canonicalName(string $departmentSlug, string $slug): ?string
    {
        $names = self::canonicalNames($departmentSlug);

        return isset($names[$slug]) ? (string) $names[$slug] : null;
    }

    public static function departmentForCanonicalSlug(string $slug): ?string
    {
        foreach (self::grouped() as $departmentSlug => $subcategories) {
            if (array_key_exists($slug, $subcategories)) {
                return $departmentSlug;
            }
        }

        return null;
    }

    public static function ariikaSubcategorySlug(string $handle): string
    {
        return self::mappedSubcategorySlug('product-scraper.ariika.collection_subcategories', $handle, 'living-room');
    }

    public static function gemmaCeramicsSubcategorySlug(string $handle): string
    {
        return self::mappedSubcategorySlug('product-scraper.gemma.collection_subcategories', $handle, 'indoor-flooring');
    }

    public static function cleopatraCeramicsSubcategorySlug(string $handle): string
    {
        return self::mappedSubcategorySlug('product-scraper.cleopatra.collection_subcategories', $handle, 'indoor-flooring');
    }

    public static function mahgoubCeramicsSubcategorySlug(string $handle): string
    {
        return self::mappedSubcategorySlug('product-scraper.mahgoub.ceramics_collection_subcategories', $handle, 'indoor-flooring');
    }

    public static function sedarSubcategorySlug(string $handle): string
    {
        return self::mappedSubcategorySlug('product-scraper.sedar.collection_subcategories', $handle, 'curtains-all');
    }

    public static function khamatoAccessorySubcategorySlug(string $handle): ?string
    {
        $mapped = config("product-scraper.khamato.accessory_collection_subcategories.{$handle}");

        return is_string($mapped) && $mapped !== '' ? $mapped : null;
    }

    public static function hansSubcategorySlug(string $handle): string
    {
        return match ($handle) {
            'sinks', 'ovens' => 'kitchen-accessories',
            default => 'kitchen-accessories',
        };
    }

    protected static function mappedSubcategorySlug(string $configKey, string $handle, string $fallback): string
    {
        $mapped = config("{$configKey}.{$handle}");

        return is_string($mapped) && $mapped !== '' ? $mapped : $fallback;
    }

    /** @return array<string, string> handle => menu subcategory slug */
    public static function ariikaCollectionSubcategories(): array
    {
        return config('product-scraper.ariika.collection_subcategories', []);
    }

    /**
     * Map legacy category slugs to their new parent subcategory slug.
     *
     * @return array<string, string> legacy_slug => subcategory_slug
     */
    public static function legacyParentMap(): array
    {
        $map = [];

        foreach (config('product-scraper.sedar.collections', []) as $handle => $name) {
            $map['sedar-'.$handle] = self::sedarSubcategorySlug($handle);
        }

        foreach (config('product-scraper.ariika.furniture_collections', []) as $handle => $name) {
            $map['ariika-'.$handle] = self::ariikaSubcategorySlug($handle);
        }

        foreach (['sinks', 'ovens'] as $handle) {
            $map['hans-'.$handle] = self::hansSubcategorySlug($handle);
        }

        foreach (config('product-scraper.khamato.collections', []) as $handle => $name) {
            $sub = self::khamatoAccessorySubcategorySlug($handle);
            if ($sub !== null) {
                $map['khamato-'.$handle] = $sub;
            }
        }

        foreach (config('product-scraper.gemma.collections', []) as $handle => $name) {
            $map['gemma-'.$handle] = self::gemmaCeramicsSubcategorySlug($handle);
        }

        foreach (config('product-scraper.cleopatra.collections', []) as $handle => $name) {
            $map['cleopatra-'.$handle] = self::cleopatraCeramicsSubcategorySlug($handle);
        }

        foreach (config('categories.mahgoub_ceramics', []) as $handle => $name) {
            $sub = self::mahgoubCeramicsSubcategorySlug($handle);
            $map['mahgoub-'.$handle] = $sub;
            $map[$handle] = $sub;
        }

        $map['cleopatra'] = 'indoor-flooring';
        $map['gemma'] = 'indoor-flooring';

        return $map;
    }

    public static function targetSubcategoryForSlug(string $slug): ?string
    {
        $map = self::legacyParentMap();

        if (isset($map[$slug])) {
            return $map[$slug];
        }

        if (self::departmentForCanonicalSlug($slug) !== null) {
            return null;
        }

        if (str_starts_with($slug, 'gemma-')) {
            return self::gemmaCeramicsSubcategorySlug(substr($slug, 6));
        }

        if (str_starts_with($slug, 'cleopatra-')) {
            return self::cleopatraCeramicsSubcategorySlug(substr($slug, 10));
        }

        if (str_starts_with($slug, 'mahgoub-')) {
            $handle = substr($slug, 8);
            if (array_key_exists($handle, config('categories.mahgoub_sanitary', []))) {
                return null;
            }

            return self::mahgoubCeramicsSubcategorySlug($handle);
        }

        if (str_starts_with($slug, 'sedar-')) {
            return self::sedarSubcategorySlug(substr($slug, 6));
        }

        if (str_starts_with($slug, 'ariika-')) {
            return self::ariikaSubcategorySlug(substr($slug, 7));
        }

        if (str_starts_with($slug, 'hans-')) {
            return self::hansSubcategorySlug(substr($slug, 5));
        }

        if (str_starts_with($slug, 'khamato-')) {
            return self::khamatoAccessorySubcategorySlug(substr($slug, 8));
        }

        return null;
    }
}
