<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Support\MahgoubCategories;
use Illuminate\Console\Command;

class MergeMahgoubCategoriesCommand extends Command
{
    protected $signature = 'categories:merge-mahgoub
                            {--dry-run : Show changes without saving}';

    protected $description = 'Merge legacy mahgoub-* categories into unified storefront categories under سيراميك and صحي';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $legacyMap = MahgoubCategories::legacySlugMap();
        $canonicalNames = MahgoubCategories::canonicalNames();
        $moved = 0;
        $merged = 0;

        foreach (['ceramics', 'sanitary'] as $parentSlug) {
            $parent = Category::query()->where('slug', $parentSlug)->first();

            if ($parent === null) {
                $this->warn("Parent category {$parentSlug} not found — skipping.");

                continue;
            }

            foreach ($legacyMap as $oldSlug => $canonicalSlug) {
                if (MahgoubCategories::parentSlugForHandle(
                    str_replace('mahgoub-', '', $oldSlug)
                ) !== $parentSlug) {
                    continue;
                }

                $old = Category::query()
                    ->where('slug', $oldSlug)
                    ->where('parent_id', $parent->id)
                    ->first();

                if ($old === null) {
                    continue;
                }

                $canonicalName = $canonicalNames[$canonicalSlug] ?? $old->name;

                $target = Category::withTrashed()->firstOrCreate(
                    ['slug' => $canonicalSlug, 'parent_id' => $parent->id],
                    ['name' => $canonicalName, 'is_active' => true, 'sort_order' => 0]
                );

                if ($target->trashed()) {
                    if (! $dryRun) {
                        $target->restore();
                    }
                }

                if (! $dryRun) {
                    $target->update(['name' => $canonicalName, 'is_active' => true]);
                }

                $productCount = $old->products()->count();

                if ($productCount > 0) {
                    $this->line("{$oldSlug} → {$canonicalSlug}: {$productCount} products");

                    if (! $dryRun) {
                        $old->products()->update(['category_id' => $target->id]);
                        $moved += $productCount;
                    }
                }

                if ($old->id !== $target->id) {
                    if (! $dryRun && $old->products()->count() === 0) {
                        $old->delete();
                    }

                    $merged++;
                }
            }
        }

        if ($dryRun) {
            $this->warn('Dry run — no changes saved.');
        } else {
            $this->info("Merged {$merged} legacy categories, moved {$moved} products.");
        }

        return self::SUCCESS;
    }
}
