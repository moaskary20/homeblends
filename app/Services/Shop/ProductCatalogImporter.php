<?php

namespace App\Services\Shop;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\Cache;

class ProductCatalogImporter
{
    protected int $categoriesUpserted = 0;

    protected int $productsCreated = 0;

    protected int $productsUpdated = 0;

    /**
     * @param  array{categories?: array<int, array<string, mixed>>, products?: array<int, array<string, mixed>>}  $catalog
     */
    public function import(array $catalog): void
    {
        $categoryMap = $this->importCategories($catalog['categories'] ?? []);

        foreach ($catalog['products'] ?? [] as $row) {
            $this->importProduct($row, $categoryMap);
        }

        Cache::forget('shop.nav.categories');
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, int>
     */
    protected function importCategories(array $rows): array
    {
        $map = [];
        $pending = collect($rows);

        while ($pending->isNotEmpty()) {
            $progress = false;

            foreach ($pending as $index => $row) {
                $parentSlug = $row['parent_slug'] ?? null;

                if ($parentSlug !== null && ! isset($map[$parentSlug])) {
                    continue;
                }

                $category = Category::withTrashed()->updateOrCreate(
                    ['slug' => (string) $row['slug']],
                    [
                        'name' => (string) $row['name'],
                        'parent_id' => $parentSlug ? $map[$parentSlug] : null,
                        'description' => $row['description'] ?? null,
                        'image' => $row['image'] ?? null,
                        'meta_title' => $row['meta_title'] ?? null,
                        'meta_description' => $row['meta_description'] ?? null,
                        'is_active' => (bool) ($row['is_active'] ?? true),
                        'sort_order' => (int) ($row['sort_order'] ?? 0),
                        'deleted_at' => null,
                    ],
                );

                if ($category->trashed()) {
                    $category->restore();
                }

                $map[$category->slug] = $category->id;
                $this->categoriesUpserted++;
                $pending->forget($index);
                $progress = true;
            }

            if (! $progress) {
                break;
            }
        }

        return $map;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, int>  $categoryMap
     */
    protected function importProduct(array $row, array $categoryMap): void
    {
        if (blank($row['sku'] ?? null) || blank($row['name'] ?? null)) {
            return;
        }

        $categorySlug = (string) ($row['category_slug'] ?? '');
        $categoryId = $categoryMap[$categorySlug] ?? Category::query()->where('slug', $categorySlug)->value('id');

        if (! $categoryId) {
            $fallback = Category::query()->where('slug', 'athath')->value('id')
                ?? Category::query()->value('id');

            if (! $fallback) {
                return;
            }

            $categoryId = $fallback;
        }

        $status = ProductStatus::tryFrom((string) ($row['status'] ?? 'published'))
            ?? ProductStatus::Published;

        $payload = [
            'category_id' => $categoryId,
            'name' => (string) $row['name'],
            'barcode' => $row['barcode'] ?? null,
            'short_description' => $row['short_description'] ?? null,
            'full_description' => $row['full_description'] ?? null,
            'regular_price' => (float) ($row['regular_price'] ?? 0),
            'discount_price' => $row['discount_price'] ?? null,
            'discount_starts_at' => $row['discount_starts_at'] ?? null,
            'discount_ends_at' => $row['discount_ends_at'] ?? null,
            'cost_price' => $row['cost_price'] ?? null,
            'stock_quantity' => (int) ($row['stock_quantity'] ?? 0),
            'low_stock_threshold' => (int) ($row['low_stock_threshold'] ?? 5),
            'weight' => $row['weight'] ?? null,
            'dimensions' => $row['dimensions'] ?? null,
            'status' => $status,
            'is_featured' => (bool) ($row['is_featured'] ?? false),
            'main_image' => $row['main_image'] ?? null,
            'meta_title' => $row['meta_title'] ?? null,
            'meta_description' => $row['meta_description'] ?? null,
        ];

        $product = Product::withTrashed()->where('sku', $row['sku'])->first();

        if ($product) {
            if ($product->trashed()) {
                $product->restore();
            }
            $product->update($payload);
            $this->productsUpdated++;
        } else {
            $payload['sku'] = (string) $row['sku'];
            $slug = (string) ($row['slug'] ?? $row['name']);
            $payload['slug'] = Product::query()->where('slug', $slug)->exists()
                ? Product::generateUniqueSlug($slug)
                : $slug;
            $product = Product::create($payload);
            $this->productsCreated++;
        }

        $this->syncImages($product, $row['images'] ?? [], $row['main_image'] ?? null);
    }

    /**
     * @param  array<int, array<string, mixed>>  $images
     */
    protected function syncImages(Product $product, array $images, ?string $mainImage): void
    {
        $product->images()->delete();

        if ($images === []) {
            if ($mainImage) {
                $product->update(['main_image' => $mainImage]);
            }

            return;
        }

        $sort = 0;

        foreach ($images as $image) {
            if (blank($image['path'] ?? null)) {
                continue;
            }

            ProductImage::create([
                'product_id' => $product->id,
                'path' => (string) $image['path'],
                'alt' => $image['alt'] ?? $product->name,
                'sort_order' => (int) ($image['sort_order'] ?? $sort),
            ]);
            $sort++;
        }

        $product->update(['main_image' => $mainImage ?? $images[0]['path'] ?? null]);
    }

    public function getCategoriesUpserted(): int
    {
        return $this->categoriesUpserted;
    }

    public function getProductsCreated(): int
    {
        return $this->productsCreated;
    }

    public function getProductsUpdated(): int
    {
        return $this->productsUpdated;
    }
}
