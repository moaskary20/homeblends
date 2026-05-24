<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Services\Payment\PaymentGatewayService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class MainCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('categories.main_departments', []) as $department) {
            $category = Category::withTrashed()->updateOrCreate(
                ['slug' => $department['slug']],
                [
                    'name' => $department['name'],
                    'parent_id' => null,
                    'description' => $department['description'] ?? null,
                    'image' => $department['image'] ?? null,
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
