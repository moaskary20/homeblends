<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Support\CategoryImageResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SyncCategoryImagesCommand extends Command
{
    protected $signature = 'categories:sync-images';

    protected $description = 'Assign expressive category photos from config and public/images/categories';

    public function handle(CategoryImageResolver $images): int
    {
        $updated = 0;

        Category::query()->orderBy('id')->each(function (Category $category) use ($images, &$updated): void {
            $configured = $this->configuredImagePath($category->slug);
            $fallback = 'images/categories/'.$category->slug.'.jpg';

            $path = $images->resolve($category->slug, $configured ?? $fallback);

            if (blank($path)) {
                $this->warn("No image for {$category->slug}");

                return;
            }

            if ($category->image !== $path) {
                $category->update(['image' => $path]);
                $updated++;
                $this->line("✓ {$category->name} → {$path}");
            }
        });

        foreach (['shop.nav.categories', 'shop.categories', 'categories.tree', 'api.v1.home'] as $key) {
            Cache::forget($key);
        }

        $this->info("Synced {$updated} category image(s).");

        return self::SUCCESS;
    }

    protected function configuredImagePath(string $slug): ?string
    {
        foreach (config('categories.main_departments', []) as $department) {
            if (($department['slug'] ?? null) === $slug) {
                return $department['image'] ?? null;
            }
        }

        foreach (config('categories.department_subcategories', []) as $subcategories) {
            if (isset($subcategories[$slug]['image'])) {
                return $subcategories[$slug]['image'];
            }
        }

        foreach (config('categories.sanitary_subcategories', []) as $mainSlug => $main) {
            if ($mainSlug === $slug) {
                return $main['image'] ?? null;
            }

            foreach ($main['children'] ?? [] as $childSlug => $child) {
                if ($childSlug === $slug) {
                    return $child['image'] ?? null;
                }
            }
        }

        if ($slug === 'accessories') {
            return 'images/categories/accessories.jpg';
        }

        return null;
    }
}
