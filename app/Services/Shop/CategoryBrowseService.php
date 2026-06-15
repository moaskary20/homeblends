<?php

namespace App\Services\Shop;

use App\Models\Attribute;
use App\Models\Category;
use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Support\CategoryCatalog;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class CategoryBrowseService
{
    public function __construct(
        protected ProductRepositoryInterface $products,
    ) {}

    /**
     * Main root departments for homepage and departments index.
     */
    public function categoriesForHome(): Collection
    {
        return Category::query()
            ->active()
            ->roots()
            ->withCount(['products' => fn ($q) => $q->published()])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Root departments with active subcategories (for main nav dropdowns).
     */
    public function categoriesForNav(): Collection
    {
        return Category::query()
            ->active()
            ->roots()
            ->with([
                'children' => fn ($q) => $this->subcategoriesQuery($q, includeEmpty: true),
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function getCategoryBySlug(string $slug): ?Category
    {
        $category = Category::query()
            ->active()
            ->where('slug', $slug)
            ->with('parent')
            ->first();

        if ($category === null) {
            return null;
        }

        $category->load([
            'children' => fn ($q) => $this->subcategoriesQuery(
                $q,
                includeEmpty: $this->shouldIncludeEmptySubcategories($category),
            ),
        ]);

        return $category;
    }

    protected function shouldIncludeEmptySubcategories(Category $category): bool
    {
        if (CategoryCatalog::isConfiguredSlug($category->slug)) {
            return true;
        }

        if ($category->parent_id === null) {
            return true;
        }

        return false;
    }

    public function shouldShowSubcategoryLanding(Category $category, array $input): bool
    {
        if ($category->children->isEmpty()) {
            return false;
        }

        if (! empty($input['all'])) {
            return false;
        }

        $filterKeys = ['q', 'min_price', 'max_price', 'in_stock', 'sort', 'attr'];

        foreach ($filterKeys as $key) {
            if ($key === 'attr') {
                if (! empty($input['attr'])) {
                    return false;
                }

                continue;
            }

            if (isset($input[$key]) && $input[$key] !== '' && $input[$key] !== null) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\Category>  $query
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\Category>
     */
    protected function subcategoriesQuery($query, bool $includeEmpty = false)
    {
        return $query
            ->active()
            ->when(! $includeEmpty, function ($query) {
                $query->where(function ($q) {
                    $q->whereHas('products', fn ($products) => $products->published())
                        ->orWhereHas('children.products', fn ($products) => $products->published());
                });
            })
            ->with([
                'children' => fn ($children) => $this->subcategoriesQuery(
                    $children,
                    includeEmpty: $includeEmpty,
                ),
            ])
            ->withCount(['products' => fn ($q) => $q->published()])
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    /**
     * @return array<int, int>
     */
    public function categoryIdsIncludingChildren(Category $category): array
    {
        $ids = collect([$category->id]);
        $toVisit = collect([$category->id]);

        while ($toVisit->isNotEmpty()) {
            $children = Category::query()
                ->active()
                ->whereIn('parent_id', $toVisit->all())
                ->pluck('id');

            $newIds = $children->diff($ids);

            if ($newIds->isEmpty()) {
                break;
            }

            $ids = $ids->merge($newIds);
            $toVisit = $newIds->values();
        }

        return $ids->values()->all();
    }

    /**
     * @return array{min: float, max: float}
     */
    public function priceRangeForCategory(array $categoryIds): array
    {
        $base = Product::query()->published()->whereIn('category_id', $categoryIds);

        $min = (float) (clone $base)->min('regular_price');
        $max = (float) (clone $base)->max('regular_price');

        $variantMin = Product::query()
            ->published()
            ->whereIn('category_id', $categoryIds)
            ->whereHas('variants')
            ->join('product_variants', 'products.id', '=', 'product_variants.product_id')
            ->min('product_variants.price');

        $variantMax = Product::query()
            ->published()
            ->whereIn('category_id', $categoryIds)
            ->whereHas('variants')
            ->join('product_variants', 'products.id', '=', 'product_variants.product_id')
            ->max('product_variants.price');

        if ($variantMin !== null) {
            $min = min($min, (float) $variantMin);
        }
        if ($variantMax !== null) {
            $max = max($max, (float) $variantMax);
        }

        return [
            'min' => floor($min),
            'max' => ceil($max > 0 ? $max : 0),
        ];
    }

    /**
     * Attribute facets available in this category (from product variants).
     */
    public function attributeFacetsForCategory(array $categoryIds): Collection
    {
        $valueIds = \App\Models\ProductVariantValue::query()
            ->whereHas('variant.product', fn ($q) => $q->published()->whereIn('category_id', $categoryIds))
            ->distinct()
            ->pluck('attribute_value_id');

        if ($valueIds->isEmpty()) {
            return collect();
        }

        return Attribute::query()
            ->whereHas('values', fn ($q) => $q->whereIn('id', $valueIds))
            ->with(['values' => fn ($q) => $q->whereIn('id', $valueIds)->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function browseProducts(array $filters, int $perPage = 12): LengthAwarePaginator
    {
        return $this->products->paginate($perPage, $filters);
    }

    public function normalizeFilters(Category $category, array $input): array
    {
        $categoryIds = $this->categoryIdsIncludingChildren($category);
        $range = $this->priceRangeForCategory($categoryIds);

        $minPrice = isset($input['min_price']) && $input['min_price'] !== ''
            ? (float) $input['min_price']
            : null;
        $maxPrice = isset($input['max_price']) && $input['max_price'] !== ''
            ? (float) $input['max_price']
            : null;

        $attributes = [];
        foreach ($input['attr'] ?? [] as $attributeId => $valueIds) {
            $valueIds = array_filter((array) $valueIds);
            if ($valueIds !== []) {
                $attributes[(int) $attributeId] = array_map('intval', $valueIds);
            }
        }

        return [
            'category_ids' => $categoryIds,
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'sort' => $input['sort'] ?? 'newest',
            'in_stock' => ! empty($input['in_stock']),
            'q' => trim((string) ($input['q'] ?? '')),
            'attributes' => $attributes,
            'price_range' => $range,
        ];
    }
}
