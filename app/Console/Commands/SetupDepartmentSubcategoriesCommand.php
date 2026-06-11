<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Support\DepartmentSubcategories;
use App\Support\SanitarySubcategories;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SetupDepartmentSubcategoriesCommand extends Command
{
    protected $signature = 'categories:setup-subcategories
                            {--dry-run : Show changes without saving}';

    protected $description = 'Create department subcategories (أثاث / سيراميك / إكسسوارات / صحي) and re-parent legacy categories';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $moved = 0;
        $created = 0;

        foreach (DepartmentSubcategories::grouped() as $departmentSlug => $subcategories) {
            $department = Category::query()->where('slug', $departmentSlug)->first();

            if ($department === null) {
                $this->warn("Department {$departmentSlug} not found — run categories:setup-main first.");

                continue;
            }

            $canonicalBySlug = [];

            foreach ($subcategories as $slug => $row) {
                $name = (string) ($row['name'] ?? $slug);

                if ($dryRun) {
                    $existing = Category::query()
                        ->where('slug', $slug)
                        ->where('parent_id', $department->id)
                        ->first();

                    if ($existing === null) {
                        $this->line("Would create {$departmentSlug}/{$slug} ({$name})");
                        $created++;
                    }

                    $canonicalBySlug[$slug] = $existing;

                    continue;
                }

                $category = Category::withTrashed()->updateOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => $name,
                        'parent_id' => $department->id,
                        'description' => $row['description'] ?? null,
                        'image' => $row['image'] ?? null,
                        'is_active' => true,
                        'sort_order' => (int) ($row['sort_order'] ?? 0),
                        'deleted_at' => null,
                    ],
                );

                if ($category->trashed()) {
                    $category->restore();
                }

                $category->update([
                    'name' => $name,
                    'parent_id' => $department->id,
                    'description' => $row['description'] ?? null,
                    'image' => $row['image'] ?? null,
                    'is_active' => true,
                    'sort_order' => (int) ($row['sort_order'] ?? 0),
                ]);

                $canonicalBySlug[$slug] = $category;
                $this->line("✓ {$department->name} → {$category->name} ({$slug})");
                $created++;
            }

            $canonicalIds = collect($canonicalBySlug)
                ->filter()
                ->map(fn (Category $category) => $category->id)
                ->all();

            $children = Category::query()
                ->where('parent_id', $department->id)
                ->when($canonicalIds !== [], fn ($query) => $query->whereNotIn('id', $canonicalIds))
                ->get();

            foreach ($children as $child) {
                $targetSlug = DepartmentSubcategories::targetSubcategoryForSlug($child->slug);

                if ($targetSlug === null || ! isset($canonicalBySlug[$targetSlug])) {
                    continue;
                }

                $target = $canonicalBySlug[$targetSlug];

                if ($dryRun) {
                    $this->line("Would move {$child->slug} → {$targetSlug}");
                    $moved++;

                    continue;
                }

                if ($child->parent_id !== $target->id) {
                    $child->update(['parent_id' => $target->id, 'is_active' => true]);
                    $this->line("↳ {$child->slug} → {$targetSlug}");
                    $moved++;
                }
            }

            $this->reparentVendorWrappers($department, $canonicalBySlug, $dryRun, $moved);
        }

        $created += $this->setupSanitaryTree($dryRun, $moved);

        if ($dryRun) {
            $this->warn("Dry run — would create/update {$created} subcategories and move {$moved} categories.");
        } else {
            $this->clearCategoryCaches();
            $this->info("Subcategories ready ({$created} upserted, {$moved} re-parented).");
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<string, Category|null>  $canonicalBySlug
     */
    protected function reparentVendorWrappers(Category $department, array $canonicalBySlug, bool $dryRun, int &$moved): void
    {
        if ($department->slug !== 'ceramics') {
            return;
        }

        foreach (['cleopatra', 'gemma'] as $vendorSlug) {
            $vendor = Category::query()
                ->where('slug', $vendorSlug)
                ->where('parent_id', $department->id)
                ->first();

            if ($vendor === null) {
                continue;
            }

            $vendorChildren = Category::query()
                ->where('parent_id', $vendor->id)
                ->get();

            foreach ($vendorChildren as $child) {
                $targetSlug = DepartmentSubcategories::targetSubcategoryForSlug($child->slug);

                if ($targetSlug === null || ! isset($canonicalBySlug[$targetSlug])) {
                    continue;
                }

                $target = $canonicalBySlug[$targetSlug];

                if ($dryRun) {
                    $this->line("Would move {$child->slug} from {$vendorSlug} → {$targetSlug}");
                    $moved++;

                    continue;
                }

                if ($child->parent_id !== $target->id) {
                    $child->update(['parent_id' => $target->id, 'is_active' => true]);
                    $this->line("↳ {$child->slug} ({$vendorSlug}) → {$targetSlug}");
                    $moved++;
                }
            }

            if (! $dryRun && $vendor->children()->count() === 0) {
                $vendor->delete();
                $this->warn("Removed empty vendor wrapper: {$vendorSlug}");
            }
        }
    }

    protected function setupSanitaryTree(bool $dryRun, int &$moved): int
    {
        $department = Category::query()->where('slug', 'sanitary')->first();

        if ($department === null) {
            $this->warn('Department sanitary not found — skipping sanitary tree.');

            return 0;
        }

        $created = 0;
        $leafBySlug = [];
        $mainBySlug = [];

        foreach (SanitarySubcategories::grouped() as $mainSlug => $mainRow) {
            $main = $this->upsertSubcategory(
                $department,
                $mainSlug,
                $mainRow,
                $dryRun,
                $created,
            );

            if ($main !== null) {
                $mainBySlug[$mainSlug] = $main;
            }

            foreach ($mainRow['children'] ?? [] as $leafSlug => $leafRow) {
                if ($main === null) {
                    continue;
                }

                $leaf = $this->upsertSubcategory(
                    $main,
                    $leafSlug,
                    $leafRow,
                    $dryRun,
                    $created,
                );

                if ($leaf !== null) {
                    $leafBySlug[$leafSlug] = $leaf;
                }
            }
        }

        $keepIds = collect($mainBySlug)
            ->merge($leafBySlug)
            ->map(fn (Category $category) => $category->id)
            ->all();

        $directChildren = Category::query()
            ->where('parent_id', $department->id)
            ->when($keepIds !== [], fn ($query) => $query->whereNotIn('id', $keepIds))
            ->get();

        foreach ($directChildren as $child) {
            $this->reparentSanitaryLegacyCategory(
                $child,
                $mainBySlug,
                $leafBySlug,
                $dryRun,
                $moved,
            );
        }

        foreach ($mainBySlug as $main) {
            $mainChildren = Category::query()
                ->where('parent_id', $main->id)
                ->when($keepIds !== [], fn ($query) => $query->whereNotIn('id', $keepIds))
                ->get();

            foreach ($mainChildren as $child) {
                $this->reparentSanitaryLegacyCategory(
                    $child,
                    $mainBySlug,
                    $leafBySlug,
                    $dryRun,
                    $moved,
                );
            }
        }

        foreach ($leafBySlug as $leafSlug => $leaf) {
            $mainSlug = SanitarySubcategories::mainSlugForLeaf($leafSlug);
            if ($mainSlug === null || ! isset($mainBySlug[$mainSlug])) {
                continue;
            }

            if (! $dryRun && $leaf->parent_id !== $mainBySlug[$mainSlug]->id) {
                $leaf->update(['parent_id' => $mainBySlug[$mainSlug]->id, 'is_active' => true]);
            }
        }

        return $created;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function upsertSubcategory(
        Category $parent,
        string $slug,
        array $row,
        bool $dryRun,
        int &$created,
    ): ?Category {
        $name = (string) ($row['name'] ?? $slug);

        if ($dryRun) {
            $existing = Category::query()
                ->where('slug', $slug)
                ->where('parent_id', $parent->id)
                ->first();

            if ($existing === null) {
                $this->line("Would create {$parent->slug}/{$slug} ({$name})");
                $created++;
            }

            return $existing;
        }

        $category = Category::withTrashed()->updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'parent_id' => $parent->id,
                'description' => $row['description'] ?? null,
                'image' => $row['image'] ?? null,
                'is_active' => true,
                'sort_order' => (int) ($row['sort_order'] ?? 0),
                'deleted_at' => null,
            ],
        );

        if ($category->trashed()) {
            $category->restore();
        }

        $category->update([
            'name' => $name,
            'parent_id' => $parent->id,
            'description' => $row['description'] ?? null,
            'image' => $row['image'] ?? null,
            'is_active' => true,
            'sort_order' => (int) ($row['sort_order'] ?? 0),
        ]);

        $this->line("✓ {$parent->name} → {$category->name} ({$slug})");
        $created++;

        return $category;
    }

    /**
     * @param  array<string, Category>  $mainBySlug
     * @param  array<string, Category>  $leafBySlug
     */
    protected function reparentSanitaryLegacyCategory(
        Category $child,
        array $mainBySlug,
        array $leafBySlug,
        bool $dryRun,
        int &$moved,
    ): void {
        $targetSlug = SanitarySubcategories::targetLeafForSlug($child->slug);

        if ($targetSlug !== null) {
            if (isset($leafBySlug[$targetSlug])) {
                $this->moveCategory($child, $leafBySlug[$targetSlug], $dryRun, $moved);

                return;
            }

            if (isset($mainBySlug[$targetSlug])) {
                $this->moveCategory($child, $mainBySlug[$targetSlug], $dryRun, $moved);

                return;
            }
        }

        $mainSlug = SanitarySubcategories::mainSlugForLeaf($child->slug);
        if ($mainSlug !== null && isset($mainBySlug[$mainSlug])) {
            $this->moveCategory($child, $mainBySlug[$mainSlug], $dryRun, $moved);
        }
    }

    protected function moveCategory(Category $child, Category $target, bool $dryRun, int &$moved): void
    {
        if ($dryRun) {
            $this->line("Would move {$child->slug} → {$target->slug}");
            $moved++;

            return;
        }

        if ($child->parent_id !== $target->id) {
            $child->update(['parent_id' => $target->id, 'is_active' => true]);
            $this->line("↳ {$child->slug} → {$target->slug}");
            $moved++;
        }
    }

    protected function clearCategoryCaches(): void
    {
        foreach (['shop.nav.categories', 'shop.categories', 'categories.tree'] as $key) {
            Cache::forget($key);
        }
    }
}
