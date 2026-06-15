<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Support\CategoryImageResolver;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class MainCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $images = app(CategoryImageResolver::class);

        foreach (config('categories.main_departments', []) as $department) {
            $slug = (string) $department['slug'];
            $image = $images->resolve($slug, $department['image'] ?? null);

            $category = Category::withTrashed()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $department['name'],
                    'parent_id' => null,
                    'description' => $department['description'] ?? null,
                    'image' => $image,
                    'is_active' => true,
                    'sort_order' => (int) ($department['sort_order'] ?? 0),
                    'deleted_at' => null,
                ],
            );

            if ($category->trashed()) {
                $category->restore();
            }

            $this->command?->line("✓ {$category->name} ({$category->slug})");
        }

        foreach (['shop.nav.categories', 'shop.categories', 'categories.tree'] as $key) {
            Cache::forget($key);
        }

        $this->command?->info('Main department categories are ready.');
    }
}
