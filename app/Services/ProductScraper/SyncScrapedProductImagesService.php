<?php

namespace App\Services\ProductScraper;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class SyncScrapedProductImagesService
{
    public function __construct(
        protected AriikaScraperService $scraper,
        protected ScrapedProductImporter $importer,
    ) {}

    /**
     * @return array{synced: int, errors: array<int, string>}
     */
    public function sync(?string $sku = null, int $limit = 20): array
    {
        $prefix = config('product-scraper.ariika.sku_prefix', 'ARIIKA-');
        $baseUrl = rtrim(config('product-scraper.ariika.base_url'), '/');

        $query = Product::query()->where('sku', 'like', $prefix.'%');

        if ($sku) {
            $query->where('sku', $sku);
        }

        $products = $query->orderByDesc('id')->limit($limit)->get();
        $synced = 0;
        $errors = [];

        foreach ($products as $product) {
            try {
                $count = $this->syncOne($product, $baseUrl);
                if ($count > 0) {
                    $synced++;
                } else {
                    $errors[] = __('ecommerce.sync_images_none', ['sku' => $product->sku]);
                }
            } catch (\Throwable $e) {
                $errors[] = __('ecommerce.sync_images_failed', [
                    'sku' => $product->sku,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return ['synced' => $synced, 'errors' => $errors];
    }

    public function syncOne(Product $product, ?string $baseUrl = null): int
    {
        $baseUrl ??= rtrim(config('product-scraper.ariika.base_url'), '/');
        $url = "{$baseUrl}/products/{$product->slug}.json";

        $response = Http::timeout(60)
            ->withHeaders(['User-Agent' => config('product-scraper.ariika.user_agent')])
            ->get($url);

        if (! $response->successful()) {
            throw new \RuntimeException("HTTP {$response->status()}");
        }

        $shopifyProduct = $response->json('product');
        if (! $shopifyProduct) {
            return 0;
        }

        $data = $this->scraper->mapShopifyProduct(
            $shopifyProduct,
            'sync',
            $product->category?->name ?? 'عام'
        );

        $urls = $data['image_urls'] ?? [];
        if ($urls === []) {
            return 0;
        }

        $this->importer->syncImagesForProduct($product, $urls);

        return $product->fresh()->images()->count();
    }

    public function scrapedProductsCount(): int
    {
        $prefix = config('product-scraper.ariika.sku_prefix', 'ARIIKA-');

        return Product::query()->where('sku', 'like', $prefix.'%')->count();
    }

    /**
     * @return Collection<int, Product>
     */
    public function scrapedProductsOptions(int $limit = 100): Collection
    {
        $prefix = config('product-scraper.ariika.sku_prefix', 'ARIIKA-');

        return Product::query()
            ->where('sku', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['id', 'sku', 'name']);
    }
}
