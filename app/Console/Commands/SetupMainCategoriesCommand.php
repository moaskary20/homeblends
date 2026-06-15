<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Support\CategoryImageResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SetupMainCategoriesCommand extends Command
{
    protected $signature = 'categories:setup-main';

    protected $description = 'Consolidate products under أثاث and create main department categories with images';

    /**
     * @return array<int, array{name: string, slug: string, sort_order: int, description: string, image_file: string, image_source: string}>
     */
    protected function departments(): array
    {
        return collect(config('categories.main_departments', []))
            ->map(fn (array $department): array => [
                'name' => $department['name'],
                'slug' => $department['slug'],
                'sort_order' => $department['sort_order'] ?? 0,
                'description' => $department['description'] ?? '',
                'image' => $department['image'] ?? null,
            ])
            ->all();
    }

    public function handle(): int
    {
        $departments = collect($this->departments());
        $furniture = null;
        $images = app(CategoryImageResolver::class);

        foreach ($departments as $department) {
            $imagePath = $images->resolve(
                (string) $department['slug'],
                $department['image'] ?? null,
            );

            $category = Category::withTrashed()->updateOrCreate(
                ['slug' => $department['slug']],
                [
                    'name' => $department['name'],
                    'parent_id' => null,
                    'description' => $department['description'],
                    'image' => $imagePath,
                    'is_active' => true,
                    'sort_order' => $department['sort_order'],
                    'deleted_at' => null,
                ],
            );

            if ($department['slug'] === 'athath') {
                $furniture = $category;
            }

            $this->line("✓ {$category->name} ({$category->slug})");
        }

        if (! $furniture) {
            $this->error('Could not resolve the أثاث category.');

            return self::FAILURE;
        }

        $moved = Product::query()->update(['category_id' => $furniture->id]);
        $this->info("Moved {$moved} product(s) to {$furniture->name}.");

        $keepIds = $departments->pluck('slug')
            ->map(fn (string $slug): int => Category::query()->where('slug', $slug)->value('id'))
            ->filter()
            ->all();

        $removed = Category::query()
            ->whereNotIn('id', $keepIds)
            ->get();

        foreach ($removed as $category) {
            $name = $category->name;
            $category->delete();
            $this->warn("Removed old category: {$name}");
        }

        $this->clearCategoryCaches();
        $this->info('Main departments are ready.');

        return self::SUCCESS;
    }

    protected function clearCategoryCaches(): void
    {
        foreach (['shop.nav.categories', 'shop.categories', 'categories.tree'] as $key) {
            Cache::forget($key);
        }
    }
}
