<?php

namespace App\Console\Commands;

use App\Models\Category;
use Illuminate\Console\Command;

class ResortCeramicsSubgroupsCommand extends Command
{
    protected $signature = 'categories:resort-ceramics-subgroups
                            {--dry-run : Show changes without saving}';

    protected $description = 'Update sort_order for Cleopatra/Gemma under ceramics';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $ceramics = Category::query()->where('slug', 'ceramics')->first();

        if ($ceramics === null) {
            $this->warn('Category ceramics not found — nothing to resort.');

            return self::SUCCESS;
        }

        $orders = [
            'cleopatra' => 1,
            'gemma' => 2,
        ];

        $updated = 0;

        foreach ($orders as $slug => $sortOrder) {
            $category = Category::query()
                ->where('slug', $slug)
                ->where('parent_id', $ceramics->id)
                ->first();

            if ($category === null) {
                continue;
            }

            if ($category->sort_order !== $sortOrder) {
                $this->line("{$slug}: {$category->sort_order} -> {$sortOrder}");

                if (! $dryRun) {
                    $category->update(['sort_order' => $sortOrder, 'is_active' => true]);
                    $updated++;
                }
            }
        }

        if ($dryRun) {
            $this->warn('Dry run — no changes saved.');
        } else {
            $this->info("Updated {$updated} categories.");
        }

        return self::SUCCESS;
    }
}

