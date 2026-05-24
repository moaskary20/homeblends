<?php

namespace App\Services\Shop;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Support\ProductMedia;
use Illuminate\Support\Collection;

class CatalogShowcaseService
{
    /**
     * @return array{
     *     title: string,
     *     tabs: array<int, array{id: int, name: string, slug: string, products: array<int, array<string, mixed>}>
     * }>|null
     */
    public function resolve(
        string $settingKey = 'homepage_catalog_showcase',
        ?array $configDefaults = null,
    ): ?array {
        $configDefaults ??= config('homepage.catalog_showcase', []);
        $config = Setting::getValue($settingKey, $configDefaults);

        if (! is_array($config) || ! ($config['is_active'] ?? true)) {
            return null;
        }

        $limit = max(4, min(16, (int) ($config['products_limit'] ?? 8)));
        $parent = $this->resolveParentCategory($config);

        if (! $parent) {
            return null;
        }

        $tabs = $this->resolveTabs($parent, $config, $limit);

        if ($tabs->isEmpty()) {
            return null;
        }

        $title = filled($config['title'] ?? null)
            ? (string) $config['title']
            : $parent->name;

        return [
            'title' => $title,
            'tabs' => $tabs->values()->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function resolveParentCategory(array $config): ?Category
    {
        if (filled($config['category_id'] ?? null)) {
            return Category::query()
                ->active()
                ->whereKey((int) $config['category_id'])
                ->first();
        }

        return Category::query()
            ->active()
            ->whereNull('parent_id')
            ->whereHas('children', fn ($q) => $q->active()->whereHas('products', fn ($p) => $p->published()))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->first();
    }

    /**
     * @param  array<string, mixed>  $config
     * @return Collection<int, array{id: int, name: string, slug: string, products: array<int, array<string, mixed>}>
     */
    protected function resolveTabs(Category $parent, array $config, int $limit): Collection
    {
        $childQuery = Category::query()
            ->active()
            ->where('parent_id', $parent->id)
            ->whereHas('products', fn ($q) => $q->published())
            ->orderBy('sort_order')
            ->orderBy('name');

        if (filled($config['subcategory_ids'] ?? null) && is_array($config['subcategory_ids'])) {
            $ids = collect($config['subcategory_ids'])->map(fn ($id) => (int) $id)->filter()->all();
            if ($ids !== []) {
                $childQuery->whereIn('id', $ids);
            }
        }

        return $childQuery
            ->get()
            ->map(function (Category $category) use ($limit): array {
                $products = Product::query()
                    ->published()
                    ->where('category_id', $category->id)
                    ->with(['variants', 'images', 'activeFlashSaleEntry.flashSale'])
                    ->latest()
                    ->limit($limit)
                    ->get()
                    ->map(fn (Product $product): array => $this->mapProduct($product))
                    ->filter(fn (array $item): bool => filled($item['thumb']))
                    ->values()
                    ->all();

                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'url' => route('shop.categories.show', $category->slug),
                    'products' => $products,
                ];
            })
            ->filter(fn (array $tab): bool => count($tab['products']) > 0);
    }

    /**
     * @return array<string, mixed>
     */
    protected function mapProduct(Product $product): array
    {
        $salePrice = (float) $product->effective_price;
        $comparePrice = (float) $product->regular_price;
        $hasDiscount = $comparePrice > $salePrice;
        $discountPercent = $hasDiscount
            ? (int) round((($comparePrice - $salePrice) / $comparePrice) * 100)
            : null;

        return [
            'id' => $product->id,
            'name' => $product->name,
            'url' => route('shop.products.show', $product->slug),
            'thumb' => ProductMedia::productThumbnail($product, ProductMedia::SIZE_CARD),
            'sale_price' => $salePrice,
            'compare_price' => $hasDiscount ? $comparePrice : null,
            'discount_percent' => $discountPercent,
            'is_out_of_stock' => (int) $product->stock_quantity <= 0,
            'swatches' => ProductMedia::catalogSwatches($product, 4, ProductMedia::SIZE_SWATCH)->all(),
        ];
    }
}
