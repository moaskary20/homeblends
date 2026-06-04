<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Support\HomeApplianceCategories;
use Illuminate\Console\Command;

class MergeHomeApplianceCategoriesCommand extends Command
{
    protected $signature = 'categories:merge-home-appliances
                            {--dry-run : Show changes without saving}';

    protected $description = 'Merge legacy sallab-* and raya-* appliance categories into unified storefront categories';

    public function handle(): int
    {
        $parent = Category::query()->where('slug', 'home-appliances')->first();

        if ($parent === null) {
            $this->warn('Parent category home-appliances not found — nothing to merge.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $legacyMap = HomeApplianceCategories::legacySlugMap();
        $canonicalNames = HomeApplianceCategories::canonicalNames();
        $moved = 0;
        $merged = 0;

        foreach ($legacyMap as $oldSlug => $canonicalSlug) {
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

        if ($dryRun) {
            $this->warn('Dry run — no changes saved.');
        } else {
            $this->info("Merged {$merged} legacy categories, moved {$moved} products.");
        }

        return self::SUCCESS;
    }
}
