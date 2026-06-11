<?php

namespace App\Services\ProductScraper;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ScrapedProductImporter
{
    protected int $created = 0;

    protected int $updated = 0;

    /** @var Collection<int, string> */
    protected Collection $errors;

    public function __construct()
    {
        $this->errors = collect();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $items
     */
    public function import(Collection $items, bool $downloadImages = true, ?callable $onProgress = null): void
    {
        $total = max(1, $items->count());
        $current = 0;

        foreach ($items as $item) {
            $current++;
            $onProgress?->__invoke($current, $total, (string) ($item['sku'] ?? ''));

            try {
                $this->importOne($item, $downloadImages);
            } catch (\Throwable $e) {
                $this->errors->push(__('ecommerce.scrape_product_error', [
                    'sku' => $item['sku'] ?? '?',
                    'message' => $e->getMessage(),
                ]));
            }
        }

        $this->clearCategoryCaches();
    }

    protected function clearCategoryCaches(): void
    {
        Cache::forget('shop.nav.categories');
    }

    /**
     * @param  array<string, mixed>  $item
     */
    protected function importOne(array $item, bool $downloadImages): void
    {
        if (blank($item['sku'] ?? null) || blank($item['name'] ?? null)) {
            return;
        }

        $category = $this->resolveCategory($item);
        $imageUrls = array_values(array_unique(array_filter(
            $item['image_urls'] ?? [],
            fn ($url) => is_string($url) && filter_var($url, FILTER_VALIDATE_URL)
        )));

        if ($imageUrls === [] && ! empty($item['main_image_url'])) {
            $imageUrls = [(string) $item['main_image_url']];
        }

        $slugSource = (string) ($item['slug'] ?? $item['name']);

        $payload = [
            'category_id' => $category->id,
            'name' => $item['name'],
            'sku' => $item['sku'],
            'short_description' => $item['short_description'] ?? null,
            'full_description' => $item['full_description'] ?? null,
            'regular_price' => (float) ($item['regular_price'] ?? 0),
            'discount_price' => $item['discount_price'] ?? null,
            'stock_quantity' => (int) ($item['stock_quantity'] ?? 0),
            'low_stock_threshold' => 5,
            'status' => ProductStatus::Published,
            'is_featured' => false,
            'meta_title' => Str::limit((string) $item['name'], 70),
            'meta_description' => Str::limit(strip_tags((string) ($item['short_description'] ?? '')), 160),
        ];

        $product = Product::withTrashed()->where('sku', $payload['sku'])->first();

        if ($product) {
            if ($product->trashed()) {
                $product->restore();
            }
            $product->update($payload);
            $this->updated++;
        } else {
            $payload['slug'] = Product::generateUniqueSlug($slugSource);
            $product = Product::create($payload);
            $this->created++;
        }

        if ($downloadImages && $imageUrls !== []) {
            $this->syncProductImages($product, $imageUrls, (string) $item['sku']);
        } elseif (! $downloadImages && $imageUrls !== []) {
            $this->syncProductImagesAsExternal($product, $imageUrls, (string) $item['name']);
        }
    }

    /**
     * @param  array<int, string>  $urls
     */
    protected function syncProductImages(Product $product, array $urls, string $sku): void
    {
        $product->images()->delete();
        $paths = [];
        $sort = 0;

        foreach ($urls as $index => $url) {
            $path = $this->downloadImage($url, $sku, $index);
            if ($path) {
                $paths[] = $path;
                ProductImage::create([
                    'product_id' => $product->id,
                    'path' => $path,
                    'alt' => $product->name,
                    'sort_order' => $sort++,
                ]);
            }
        }

        $product->update(['main_image' => $paths[0] ?? null]);
    }

    /**
     * @param  array<int, string>  $urls
     */
    protected function syncProductImagesAsExternal(Product $product, array $urls, string $alt): void
    {
        $product->images()->delete();
        $sort = 0;

        foreach ($urls as $url) {
            ProductImage::create([
                'product_id' => $product->id,
                'path' => $url,
                'alt' => $alt,
                'sort_order' => $sort++,
            ]);
        }

        $product->update(['main_image' => $urls[0] ?? null]);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    protected function resolveCategory(array $item): Category
    {
        $grandparent = null;
        $grandparentSlug = trim((string) ($item['grandparent_category_slug'] ?? ''));
        $grandparentName = trim((string) ($item['grandparent_category_name'] ?? ''));

        if ($grandparentSlug !== '' && $grandparentName !== '') {
            $grandparent = Category::withTrashed()->firstOrCreate(
                ['slug' => $grandparentSlug],
                ['name' => $grandparentName, 'is_active' => true, 'sort_order' => 0]
            );

            if ($grandparent->trashed()) {
                $grandparent->restore();
            }

            $grandparent->update([
                'name' => $grandparentName,
                'is_active' => true,
            ]);
        }

        $parentSlug = (string) ($item['parent_category_slug'] ?? 'athath');
        $parentName = (string) ($item['parent_category_name'] ?? 'أثاث');

        $parentSortOrder = 0;
        if ($grandparentSlug === 'ceramics') {
            $parentSortOrder = match ($parentSlug) {
                'indoor-flooring' => 1,
                'walls' => 2,
                'outdoor-flooring' => 3,
                'porcelain' => 4,
                default => 0,
            };
        }

        $parent = Category::withTrashed()->firstOrCreate(
            ['slug' => $parentSlug],
            [
                'name' => $parentName,
                'parent_id' => $grandparent?->id,
                'is_active' => true,
                'sort_order' => $parentSortOrder,
            ]
        );

        if ($parent->trashed()) {
            $parent->restore();
        }

        $parent->update([
            'name' => $parentName,
            'parent_id' => $grandparent?->id,
            'is_active' => true,
            'sort_order' => $parentSortOrder,
        ]);

        $childSlug = (string) ($item['category_slug'] ?? Category::slugify((string) $item['category_name']));
        $childName = (string) ($item['category_name'] ?? 'عام');

        $child = Category::withTrashed()->updateOrCreate(
            ['slug' => $childSlug],
            [
                'name' => $childName,
                'parent_id' => $parent->id,
                'is_active' => true,
                'sort_order' => 0,
            ]
        );

        if ($child->trashed()) {
            $child->restore();
        }

        if ($grandparentSlug === 'ceramics') {
            return $parent;
        }

        return $child;
    }

    protected function downloadImage(string $url, string $sku, int $index = 0): ?string
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        $response = Http::timeout(30)
            ->withHeaders(['User-Agent' => config('product-scraper.ariika.user_agent')])
            ->get($url);

        if (! $response->successful()) {
            return null;
        }

        $extension = $this->guessExtension($url, $response->header('Content-Type'));
        $suffix = $index > 0 ? '-'.$index : '';
        $filename = 'products/scraped/'.Str::slug($sku).$suffix.'.'.$extension;
        Storage::disk('public')->put($filename, $response->body());

        return $filename;
    }

    protected function guessExtension(string $url, ?string $contentType): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $ext = pathinfo((string) $path, PATHINFO_EXTENSION);
        if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            return strtolower($ext === 'jpeg' ? 'jpg' : $ext);
        }

        return match (true) {
            str_contains((string) $contentType, 'png') => 'png',
            str_contains((string) $contentType, 'webp') => 'webp',
            default => 'jpg',
        };
    }

    /**
     * @param  array<int, string>  $imageUrls
     */
    public function syncImagesForProduct(Product $product, array $imageUrls): void
    {
        if ($imageUrls === []) {
            return;
        }

        $this->syncProductImages($product, $imageUrls, $product->sku);
    }

    public function getCreatedCount(): int
    {
        return $this->created;
    }

    public function getUpdatedCount(): int
    {
        return $this->updated;
    }

    public function getErrors(): Collection
    {
        return $this->errors;
    }
}
