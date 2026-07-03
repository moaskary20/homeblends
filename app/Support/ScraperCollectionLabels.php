<?php

namespace App\Support;

class ScraperCollectionLabels
{
    /**
     * @param  array<string, string>  $collections
     * @return array<string, string>
     */
    public static function forDepartment(
        array $collections,
        string $departmentSlug,
        callable $subcategoryResolver,
    ): array {
        $options = [];

        foreach ($collections as $handle => $sourceLabel) {
            $subSlug = $subcategoryResolver($handle);
            $menuName = DepartmentSubcategories::canonicalName($departmentSlug, $subSlug);

            $options[$handle] = $menuName !== null
                ? "{$menuName} — {$sourceLabel}"
                : $sourceLabel;
        }

        return $options;
    }

    /**
     * @param  array<string, string>  $collections
     * @return array<string, string>
     */
    public static function forSanitary(array $collections, callable $leafResolver): array
    {
        $options = [];

        foreach ($collections as $handle => $sourceLabel) {
            $options[$handle] = self::sanitary($handle, $sourceLabel, $leafResolver);
        }

        return $options;
    }

    public static function sanitary(string $handle, string $sourceLabel, callable $leafResolver): string
    {
        $leaf = $leafResolver($handle);

        if ($leaf === null) {
            return $sourceLabel;
        }

        if (SanitarySubcategories::isMainSlug($leaf)) {
            $name = SanitarySubcategories::name($leaf);

            return $name !== null ? "{$name} — {$sourceLabel}" : $sourceLabel;
        }

        $leafName = SanitarySubcategories::name($leaf);
        $mainSlug = SanitarySubcategories::mainSlugForLeaf($leaf);
        $mainName = $mainSlug !== null ? SanitarySubcategories::name($mainSlug) : null;

        $menuPath = $mainName !== null && $leafName !== null
            ? "{$mainName} / {$leafName}"
            : ($leafName ?? $sourceLabel);

        return "{$menuPath} — {$sourceLabel}";
    }
}
