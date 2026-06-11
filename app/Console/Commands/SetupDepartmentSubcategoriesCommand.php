<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
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
        $deleted = 0;
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

            if ($department->slug === 'ceramics') {
                $this->purgeCeramicsExtras($department, $canonicalBySlug, $dryRun, $moved, $deleted);
            }
        }

        $created += $this->setupSanitaryTree($dryRun, $moved);

        if ($dryRun) {
            $this->warn("Dry run — would create/update {$created} subcategories, move {$moved} categories, delete {$deleted} extras.");
        } else {
            $this->clearCategoryCaches();
            $this->info("Subcategories ready ({$created} upserted, {$moved} re-parented, {$deleted} ceramics extras removed).");
        }

        return self::SUCCESS;
    }

    /**
     * Flatten سيراميك to only the four canonical subcategories.
     *
     * @param  array<string, Category|null>  $canonicalBySlug
     */
    protected function purgeCeramicsExtras(
        Category $ceramics,
        array $canonicalBySlug,
        bool $dryRun,
        int &$moved,
        int &$deleted,
    ): void {
        $canonical = collect($canonicalBySlug)->filter()->values();
        $canonicalIds = $canonical->map(fn (Category $category) => $category->id)->all();
        $canonicalSlugs = $canonical->map(fn (Category $category) => $category->slug)->all();

        while (true) {
            $extras = Category::query()
                ->with('parent')
                ->whereNotIn('id', $canonicalIds)
                ->get()
                ->filter(fn (Category $category) => $this->isUnderCeramics($category, $ceramics->id))
                ->sortByDesc(fn (Category $category) => $this->categoryDepth($category, $ceramics->id));

            if ($extras->isEmpty()) {
                break;
            }

            $removedThisRound = 0;

            foreach ($extras as $extra) {
                $targetSlug = $this->resolveCeramicsTargetSlug($extra, $canonicalBySlug);
                $target = $canonicalBySlug[$targetSlug] ?? null;

                if ($target === null) {
                    continue;
                }

                $productCount = $extra->products()->count();

                if ($productCount > 0) {
                    if ($dryRun) {
                        $this->line("Would move {$productCount} product(s) from {$extra->slug} → {$targetSlug}");
                        $moved += $productCount;
                    } else {
                        Product::query()
                            ->where('category_id', $extra->id)
                            ->update(['category_id' => $target->id]);
                        $this->line("↳ moved {$productCount} product(s) from {$extra->slug} → {$targetSlug}");
                        $moved += $productCount;
                    }
                }

                if (Category::query()->where('parent_id', $extra->id)->exists()) {
                    continue;
                }

                if ($dryRun) {
                    $this->line("Would delete {$extra->slug}");
                    $deleted++;
                    $removedThisRound++;

                    continue;
                }

                $extra->delete();
                $this->warn("Removed ceramics extra: {$extra->slug}");
                $deleted++;
                $removedThisRound++;
            }

            if ($dryRun || $removedThisRound === 0) {
                break;
            }
        }

        if (! $dryRun) {
            foreach ($canonical as $category) {
                if ($category->parent_id !== $ceramics->id) {
                    $category->update(['parent_id' => $ceramics->id, 'is_active' => true]);
                }
            }

            $strayDirectChildren = Category::query()
                ->where('parent_id', $ceramics->id)
                ->whereNotIn('slug', $canonicalSlugs)
                ->pluck('slug');

            if ($strayDirectChildren->isNotEmpty()) {
                $this->warn('Ceramics still has unexpected children: '.$strayDirectChildren->implode(', '));
            }
        }
    }

    protected function isUnderCeramics(Category $category, int $ceramicsId): bool
    {
        $current = $category;

        while ($current->parent_id !== null) {
            if ($current->parent_id === $ceramicsId) {
                return true;
            }

            $current = $current->parent ?? Category::query()->find($current->parent_id);

            if ($current === null) {
                break;
            }
        }

        return false;
    }

    protected function categoryDepth(Category $category, int $ceramicsId): int
    {
        $depth = 0;
        $current = $category;

        while ($current->parent_id !== null && $current->parent_id !== $ceramicsId) {
            $depth++;
            $current = $current->parent ?? Category::query()->find($current->parent_id);

            if ($current === null) {
                break;
            }
        }

        return $depth;
    }

    /**
     * @param  array<string, Category|null>  $canonicalBySlug
     */
    protected function resolveCeramicsTargetSlug(Category $category, array $canonicalBySlug): string
    {
        $mapped = DepartmentSubcategories::targetSubcategoryForSlug($category->slug);

        if ($mapped !== null && isset($canonicalBySlug[$mapped])) {
            return $mapped;
        }

        $parent = $category->parent;

        if ($parent !== null && isset($canonicalBySlug[$parent->slug])) {
            return $parent->slug;
        }

        return 'indoor-flooring';
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
