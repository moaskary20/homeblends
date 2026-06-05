<?php

namespace App\Console\Commands;

use App\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class RestructureCeramicsVendorsCommand extends Command
{
    protected $signature = 'categories:restructure-ceramics-vendors
                            {--dry-run : Show changes without saving}';

    protected $description = 'Group Cleopatra/Gemma subcategories under vendor nodes below ceramics';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $ceramics = Category::query()->where('slug', 'ceramics')->first();

        if ($ceramics === null) {
            $this->warn('Category ceramics not found — nothing to restructure.');

            return self::SUCCESS;
        }

        $vendors = [
            'cleopatra' => [
                'name' => 'Cleopatra',
                'sort_order' => 1,
                'slug_prefix' => 'cleopatra-',
                'name_prefix' => 'Cleopatra — ',
            ],
            'gemma' => [
                'name' => 'Gemma',
                'sort_order' => 2,
                'slug_prefix' => 'gemma-',
                'name_prefix' => 'Gemma — ',
            ],
        ];

        $moved = 0;

        foreach ($vendors as $slug => $config) {
            if ($dryRun) {
                $vendor = Category::query()
                    ->where('slug', $slug)
                    ->where('parent_id', $ceramics->id)
                    ->first();

                if ($vendor === null) {
                    $this->line("Would create vendor: {$config['name']} ({$slug})");
                }
            } else {
                $vendor = Category::withTrashed()->firstOrCreate(
                    ['slug' => $slug, 'parent_id' => $ceramics->id],
                    [
                        'name' => $config['name'],
                        'sort_order' => $config['sort_order'],
                        'is_active' => true,
                    ]
                );

                if ($vendor->trashed()) {
                    $vendor->restore();
                }

                $vendor->update([
                    'name' => $config['name'],
                    'parent_id' => $ceramics->id,
                    'sort_order' => $config['sort_order'],
                    'is_active' => true,
                ]);
            }

            $candidates = Category::query()
                ->where('parent_id', $ceramics->id)
                ->where('slug', '!=', $slug)
                ->where(function ($query) use ($config) {
                    $query->where('slug', 'like', $config['slug_prefix'].'%')
                        ->orWhere('name', 'like', $config['name_prefix'].'%');
                })
                ->get();

            foreach ($candidates as $child) {
                $cleanName = $this->stripVendorPrefix($child->name, $config['name'], $config['name_prefix']);

                $this->line("{$child->slug} → {$slug}/{$child->slug} ({$child->name} → {$cleanName})");

                if (! $dryRun) {
                    $child->update([
                        'parent_id' => $vendor->id,
                        'name' => $cleanName,
                        'is_active' => true,
                    ]);
                    $moved++;
                }
            }
        }

        if ($dryRun) {
            $this->warn('Dry run — no changes saved.');
        } else {
            Cache::forget('shop.nav.categories');
            $this->info("Restructured {$moved} categories under Cleopatra/Gemma.");
        }

        return self::SUCCESS;
    }

    protected function stripVendorPrefix(string $name, string $vendorName, string $prefix): string
    {
        $patterns = [
            '/^'.preg_quote($prefix, '/').'/u',
            '/^'.preg_quote($vendorName, '/').'\s*[-–—]\s*/u',
        ];

        foreach ($patterns as $pattern) {
            $clean = preg_replace($pattern, '', $name);

            if (is_string($clean) && trim($clean) !== '') {
                return trim($clean);
            }
        }

        return trim($name);
    }
}
