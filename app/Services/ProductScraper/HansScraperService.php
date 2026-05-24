<?php

namespace App\Services\ProductScraper;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class HansScraperService
{
    protected string $baseUrl;

    protected string $userAgent;

    protected int $connectTimeout;

    protected int $timeout;

    protected int $retryTimes;

    protected int $retrySleepMs;

    protected int $delayMs;

    protected string $skuPrefix;

    /** @var array<string, string> */
    protected array $collections;

    /** @var Collection<int, array{handle: string, message: string}> */
    protected Collection $scrapeErrors;

    public function __construct()
    {
        $config = config('product-scraper.hans');
        $this->baseUrl = rtrim($config['base_url'], '/');
        $this->userAgent = $config['user_agent'];
        $this->connectTimeout = (int) ($config['connect_timeout'] ?? 30);
        $this->timeout = (int) ($config['request_timeout'] ?? 90);
        $this->retryTimes = (int) ($config['retry_times'] ?? 3);
        $this->retrySleepMs = (int) ($config['retry_sleep_ms'] ?? 1500);
        $this->delayMs = (int) ($config['delay_ms'] ?? 500);
        $this->skuPrefix = $config['sku_prefix'];
        $this->collections = $config['collections'];
        $this->scrapeErrors = collect();
    }

    /** @return array<string, string> */
    public function getCollectionOptions(): array
    {
        return $this->collections;
    }

    /** @return Collection<int, array{handle: string, message: string}> */
    public function getScrapeErrors(): Collection
    {
        return $this->scrapeErrors;
    }

    /**
     * @param  array<int, string>  $collectionHandles
     * @return Collection<int, array<string, mixed>>
     */
    public function scrapeCollections(array $collectionHandles, int $maxPerCollection = 10): Collection
    {
        $products = collect();
        $this->scrapeErrors = collect();

        foreach ($collectionHandles as $handle) {
            if (! isset($this->collections[$handle])) {
                $this->scrapeErrors->push([
                    'handle' => $handle,
                    'message' => __('ecommerce.scrape_hans_unknown_collection', ['handle' => $handle]),
                ]);

                continue;
            }

            $categoryName = $this->collections[$handle];

            try {
                $batch = $this->fetchCollectionProducts($handle, $maxPerCollection)
                    ->map(fn (array $product) => $this->mapShopifyProduct($product, $handle, $categoryName));

                $products = $products->merge($batch);
            } catch (\Throwable $e) {
                $this->scrapeErrors->push([
                    'handle' => $handle,
                    'message' => $e->getMessage(),
                ]);
            }

            $this->pause();
        }

        return $products->unique('sku')->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function fetchCollectionProducts(string $handle, int $limit = 10): Collection
    {
        $url = "{$this->baseUrl}/collections/{$handle}/products.json";
        $response = $this->http()->get($url, ['limit' => min($limit, 250)]);

        if (! $response->successful()) {
            throw new \RuntimeException(__('ecommerce.scrape_hans_collection_failed', [
                'handle' => $handle,
                'status' => $response->status(),
            ]));
        }

        $items = $response->json('products', []);

        return collect($items)->take($limit);
    }

    /**
     * @param  array<string, mixed>  $product
     * @return array<string, mixed>
     */
    public function mapShopifyProduct(array $product, string $collectionHandle, string $categoryName): array
    {
        $variants = collect($product['variants'] ?? []);
        $firstVariant = $variants->first();
        $available = $variants->contains(fn (array $v) => ($v['available'] ?? false) === true);

        $regularPrice = $this->lowestPrice($variants, 'price');
        $comparePrice = $this->lowestComparePrice($variants, $regularPrice);
        $sku = $this->resolveSku($product, $firstVariant);
        $imageUrls = $this->collectImageUrls($product, $variants);

        $bodyHtml = (string) ($product['body_html'] ?? '');
        $plain = trim(preg_replace('/\s+/', ' ', strip_tags($bodyHtml)) ?: '');

        return [
            'sku' => $sku,
            'name' => (string) ($product['title'] ?? ''),
            'slug' => (string) ($product['handle'] ?? ''),
            'category_name' => $categoryName,
            'category_slug' => 'hans-'.$collectionHandle,
            'parent_category_name' => config('product-scraper.hans.parent_category.name'),
            'parent_category_slug' => config('product-scraper.hans.parent_category.slug'),
            'short_description' => Str::limit($plain, 500),
            'full_description' => $bodyHtml !== '' ? $bodyHtml : null,
            'main_image_url' => $imageUrls[0] ?? null,
            'image_urls' => $imageUrls,
            'regular_price' => $regularPrice,
            'discount_price' => $comparePrice,
            'stock_quantity' => $available ? max(1, $variants->where('available', true)->count()) : 0,
            'source_url' => "{$this->baseUrl}/products/".($product['handle'] ?? ''),
            'vendor' => (string) ($product['vendor'] ?? 'HANS'),
            'product_type' => (string) ($product['product_type'] ?? ''),
            'tags' => $product['tags'] ?? [],
        ];
    }

    /**
     * @param  array<string, mixed>|null  $firstVariant
     */
    protected function resolveSku(array $product, ?array $firstVariant): string
    {
        $variantSku = trim((string) ($firstVariant['sku'] ?? ''));
        if ($variantSku !== '') {
            return $this->skuPrefix.$variantSku;
        }

        $id = (string) ($product['id'] ?? $product['handle'] ?? Str::random(8));

        return $this->skuPrefix.$id;
    }

    /**
     * @return array<int, string>
     */
    protected function collectImageUrls(array $product, Collection $variants): array
    {
        $urls = [];

        foreach ($product['images'] ?? [] as $image) {
            $src = $image['src'] ?? null;
            if (is_string($src) && $src !== '') {
                $urls[] = $src;
            }
        }

        foreach ($variants as $variant) {
            $src = $variant['featured_image']['src'] ?? null;
            if (is_string($src) && $src !== '') {
                $urls[] = $src;
            }
        }

        return array_values(array_unique($urls));
    }

    protected function lowestPrice(Collection $variants, string $field): float
    {
        $prices = $variants
            ->map(fn (array $v) => (float) ($v[$field] ?? 0))
            ->filter(fn (float $p) => $p > 0);

        return $prices->isEmpty() ? 0.0 : (float) $prices->min();
    }

    protected function lowestComparePrice(Collection $variants, float $regularPrice): ?float
    {
        $compare = $variants
            ->map(fn (array $v) => (float) ($v['compare_at_price'] ?? 0))
            ->filter(fn (float $p) => $p > $regularPrice);

        if ($compare->isEmpty()) {
            return null;
        }

        return (float) $compare->min();
    }

    protected function http(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withHeaders(['User-Agent' => $this->userAgent])
            ->connectTimeout($this->connectTimeout)
            ->timeout($this->timeout)
            ->retry(
                $this->retryTimes,
                $this->retrySleepMs,
                fn (\Throwable $e) => $e instanceof ConnectionException
                    || ($e instanceof RequestException && $e->response?->serverError())
            )
            ->acceptJson();
    }

    protected function pause(): void
    {
        if ($this->delayMs > 0) {
            usleep($this->delayMs * 1000);
        }
    }

    public function ping(): bool
    {
        try {
            $response = $this->http()->get("{$this->baseUrl}/collections/sinks/products.json", [
                'limit' => 1,
            ]);

            return $response->successful() && count($response->json('products', [])) > 0;
        } catch (ConnectionException) {
            return false;
        }
    }
}
