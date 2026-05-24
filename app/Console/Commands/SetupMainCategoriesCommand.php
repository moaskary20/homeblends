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
        return collect(config('categories.main_departments', []))
            ->map(fn (array $department): array => [
                'name' => $department['name'],
                'slug' => $department['slug'],
                'sort_order' => $department['sort_order'] ?? 0,
                'description' => $department['description'] ?? '',
                'image' => $department['image'] ?? null,
                'image_file' => basename((string) ($department['image'] ?? '')),
                'image_source' => match ($department['slug'] ?? '') {
                    'athath' => 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?w=900&q=80&auto=format',
                    'ceramics' => 'https://images.unsplash.com/photo-1620626011761-996317b8d101?w=900&q=80&auto=format',
                    'accessories' => 'https://images.unsplash.com/photo-1615874959474-d609969a20ed?w=900&q=80&auto=format',
                    'textiles' => 'https://images.unsplash.com/photo-1522771739844-6a9f6d5f14af?w=900&q=80&auto=format',
                    default => '',
                },
            ])
            ->all();
    }

    public function handle(): int
    {
        $departments = collect($this->departments());
        $furniture = null;

        foreach ($departments as $department) {
            $imagePath = $this->resolveCategoryImage(
                $department['image'] ?? null,
                $department['image_file'] ?? '',
                $department['image_source'] ?? '',
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

    protected function resolveCategoryImage(?string $publicRelative, string $filename, string $sourceUrl): string
    {
        if (filled($publicRelative) && is_file(public_path($publicRelative))) {
            return $publicRelative;
        }

        $relativePath = 'categories/'.$filename;
        $disk = Storage::disk('public');

        if ($disk->exists($relativePath) && $disk->size($relativePath) > 0) {
            return $relativePath;
        }

        if ($filename === '' || $sourceUrl === '') {
            return $publicRelative ?: $relativePath;
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

        return $publicRelative ?: $sourceUrl;
    }

    protected function clearCategoryCaches(): void
    {
        foreach (['shop.nav.categories', 'shop.categories', 'categories.tree'] as $key) {
            Cache::forget($key);
        }
    }
}
