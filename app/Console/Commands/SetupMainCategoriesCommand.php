<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class SetupMainCategoriesCommand extends Command
{
    protected $signature = 'categories:setup-main';

    protected $description = 'Consolidate products under أثاث and create main department categories with images';

    /**
     * @return array<int, array{name: string, slug: string, sort_order: int, description: string, image_file: string, image_source: string}>
     */
    protected function departments(): array
    {
        return [
            [
                'name' => 'أثاث',
                'slug' => 'athath',
                'sort_order' => 1,
                'description' => 'أثاث داخلي وخارجي لكل غرف المنزل',
                'image_file' => 'athath.jpg',
                'image_source' => 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?w=900&q=80&auto=format',
            ],
            [
                'name' => 'سيراميك',
                'slug' => 'ceramics',
                'sort_order' => 2,
                'description' => 'سيراميك وبورcelain وتشطيبات للأرضيات والجدران',
                'image_file' => 'ceramics.jpg',
                'image_source' => 'https://images.unsplash.com/photo-1620626011761-996317b8d101?w=900&q=80&auto=format',
            ],
            [
                'name' => 'إكسسوارات',
                'slug' => 'accessories',
                'sort_order' => 3,
                'description' => 'إكسسوارات وديكورات تكمّل أناقة مساحتك',
                'image_file' => 'accessories.jpg',
                'image_source' => 'https://images.unsplash.com/photo-1615874959474-d609969a20ed?w=900&q=80&auto=format',
            ],
            [
                'name' => 'منسوجات',
                'slug' => 'textiles',
                'sort_order' => 4,
                'description' => 'مفروشات، ملايات، وستائر بجودة فاخرة',
                'image_file' => 'textiles.jpg',
                'image_source' => 'https://images.unsplash.com/photo-1522771739844-6a9f6d5f14af?w=900&q=80&auto=format',
            ],
        ];
    }

    public function handle(): int
    {
        $departments = collect($this->departments());
        $furniture = null;

        foreach ($departments as $department) {
            $imagePath = $this->resolveCategoryImage(
                $department['image_file'],
                $department['image_source'],
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

    protected function resolveCategoryImage(string $filename, string $sourceUrl): string
    {
        $relativePath = 'categories/'.$filename;
        $disk = Storage::disk('public');

        if ($disk->exists($relativePath) && $disk->size($relativePath) > 0) {
            return $relativePath;
        }

        $disk->makeDirectory('categories');

        try {
            $response = Http::timeout(45)->get($sourceUrl);

            if ($response->successful() && strlen($response->body()) > 1024) {
                $disk->put($relativePath, $response->body());
                $this->line("  ↳ downloaded {$relativePath}");

                return $relativePath;
            }
        } catch (\Throwable $exception) {
            $this->warn("  ↳ download failed for {$filename}: {$exception->getMessage()}");
        }

        return $sourceUrl;
    }

    protected function clearCategoryCaches(): void
    {
        foreach (['shop.nav.categories', 'shop.categories', 'categories.tree'] as $key) {
            Cache::forget($key);
        }
    }
}
