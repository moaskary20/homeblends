<?php

namespace App\Services\ProductScraper;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class KhamatoScraperService
{
    protected string $baseUrl;

    protected string $apiUrl;

    protected string $userAgent;

    protected int $connectTimeout;

    protected int $timeout;

    protected int $retryTimes;

    protected int $retrySleepMs;

    protected int $delayMs;

    protected string $skuPrefix;

    /** @var array{slug: string, name: string} */
    protected array $parentCategory;

    /** @var array<string, string> */
    protected array $collections;

    /** @var array<string, array{path: string}> */
    protected array $collectionParams;

    /** @var Collection<int, array{handle: string, message: string}> */
    protected Collection $scrapeErrors;

    public function __construct()
    {
        $config = config('product-scraper.khamato');
        $this->baseUrl = rtrim($config['base_url'], '/');
        $this->apiUrl = rtrim($config['api_url'], '/');
        $this->userAgent = $config['user_agent'];
        $this->connectTimeout = (int) ($config['connect_timeout'] ?? 30);
        $this->timeout = (int) ($config['request_timeout'] ?? 90);
        $this->retryTimes = (int) ($config['retry_times'] ?? 3);
        $this->retrySleepMs = (int) ($config['retry_sleep_ms'] ?? 1500);
        $this->delayMs = (int) ($config['delay_ms'] ?? 500);
        $this->skuPrefix = $config['sku_prefix'];
        $this->parentCategory = $config['parent_category'];
        $this->collections = $config['collections'];
        $this->collectionParams = $config['collection_params'];
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
            if (! isset($this->collections[$handle], $this->collectionParams[$handle])) {
                $this->scrapeErrors->push([
                    'handle' => $handle,
                    'message' => __('ecommerce.scrape_khamato_unknown_collection', ['handle' => $handle]),
                ]);

                continue;
            }

            $categoryName = $this->collections[$handle];

            try {
                $batch = $this->fetchCollectionProducts($handle, $maxPerCollection)
                    ->map(fn (array $product) => $this->mapProduct($product, $handle, $categoryName));

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
        $path = (string) ($this->collectionParams[$handle]['path'] ?? $handle);
        $response = $this->http()->get("{$this->baseUrl}/{$path}");

        if (! $response->successful()) {
            throw new \RuntimeException(__('ecommerce.scrape_khamato_collection_failed', [
                'handle' => $handle,
                'status' => $response->status(),
            ]));
        }

        if (! preg_match_all('/prod-id="(\d+)"/', $response->body(), $matches)) {
            return collect();
        }

        $productIds = collect($matches[1])->unique()->take($limit)->values();
        $products = collect();

        foreach ($productIds as $productId) {
            $detail = $this->fetchProduct((string) $productId);

            if ($detail !== null) {
                $products->push($detail);
            }

            $this->pause();
        }

        return $products;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function fetchProduct(string $productId): ?array
    {
        try {
            $response = $this->http()->get("{$this->apiUrl}/products/{$productId}");
        } catch (RequestException) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json('data');

        return is_array($data) ? $data : null;
    }

    /**
     * @param  array<string, mixed>  $product
     * @return array<string, mixed>
     */
    protected function mapProduct(array $product, string $handle, string $categoryName): array
    {
        $regularPrice = $this->parsePrice((string) ($product['price'] ?? ''));
        $oldPrice = $this->parsePrice((string) ($product['old_price'] ?? ''));
        $discountPrice = $oldPrice > $regularPrice && $regularPrice > 0 ? $regularPrice : null;
        $regularPrice = $oldPrice > $regularPrice && $oldPrice > 0 ? $oldPrice : $regularPrice;

        $descriptionHtml = $this->resolveDescriptionHtml($product);
        $plain = trim(preg_replace('/\s+/', ' ', strip_tags($descriptionHtml)) ?: '');
        $imageUrls = $this->collectImageUrls($product);
        $sku = trim((string) ($product['sku'] ?? $product['id'] ?? ''));
        $slug = (string) ($product['url_key'] ?? Str::slug((string) ($product['name'] ?? $sku)));

        return [
            'sku' => $this->skuPrefix.$sku,
            'name' => (string) ($product['name'] ?? ''),
            'slug' => $slug,
            'category_name' => $categoryName,
            'category_slug' => 'khamato-'.$handle,
            'parent_category_name' => $this->parentCategory['name'],
            'parent_category_slug' => $this->parentCategory['slug'],
            'short_description' => Str::limit($plain, 500),
            'full_description' => $descriptionHtml !== '' ? $descriptionHtml : null,
            'main_image_url' => $imageUrls[0] ?? null,
            'image_urls' => $imageUrls,
            'regular_price' => $regularPrice,
            'discount_price' => $discountPrice,
            'stock_quantity' => ($product['in_stock'] ?? false) ? 1 : 0,
            'source_url' => (string) ($product['full_path_url'] ?? "{$this->baseUrl}/{$slug}"),
            'vendor' => (string) ($product['supplier'] ?? 'Khamato'),
            'product_type' => (string) ($product['type'] ?? ''),
            'tags' => array_values(array_filter([
                (string) ($product['supplier'] ?? ''),
                (string) ($product['category_name'] ?? ''),
            ])),
        ];
    }

    /**
     * @param  array<string, mixed>  $product
     */
    protected function resolveDescriptionHtml(array $product): string
    {
        $extraInfo = $product['extra_info'] ?? [];

        if (! is_array($extraInfo)) {
            return '';
        }

        $parts = array_values(array_filter(
            $extraInfo,
            fn ($value) => is_string($value) && trim(strip_tags($value)) !== ''
        ));

        return $parts !== [] ? (string) reset($parts) : '';
    }

    /**
     * @param  array<string, mixed>  $product
     * @return array<int, string>
     */
    protected function collectImageUrls(array $product): array
    {
        $urls = [];

        foreach ($product['images'] ?? [] as $image) {
            if (! is_array($image)) {
                continue;
            }

            foreach (['original_image_url', 'large_image_url', 'medium_image_url'] as $key) {
                $url = $image[$key] ?? null;

                if (is_string($url) && $url !== '') {
                    $urls[] = $url;
                }
            }
        }

        $baseImage = $product['base_image'] ?? null;

        if (is_array($baseImage)) {
            foreach (['original_image_url', 'large_image_url', 'medium_image_url'] as $key) {
                $url = $baseImage[$key] ?? null;

                if (is_string($url) && $url !== '') {
                    $urls[] = $url;
                }
            }
        }

        return array_values(array_unique($urls));
    }

    protected function parsePrice(string $value): float
    {
        $normalized = str_replace([',', '٬', ' '], '', $value);
        $normalized = preg_replace('/[^\d.]/', '', $normalized) ?? '';

        return $normalized !== '' ? (float) $normalized : 0.0;
    }

    protected function http(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withHeaders([
            'User-Agent' => $this->userAgent,
            'Accept' => 'application/json, text/html',
            'Accept-Language' => 'ar-EG,ar;q=0.9',
        ])
            ->connectTimeout($this->connectTimeout)
            ->timeout($this->timeout)
            ->retry(
                $this->retryTimes,
                $this->retrySleepMs,
                fn (\Throwable $e) => $e instanceof ConnectionException
                    || ($e instanceof RequestException && $e->response?->serverError())
            );
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
            $path = (string) (config('product-scraper.khamato.collection_params.door-accessories.path')
                ?? 'doors-and-kitchen-hardware/door-accessories');
            $response = $this->http()->get("{$this->baseUrl}/{$path}");

            return $response->successful() && str_contains($response->body(), 'prod-id=');
        } catch (ConnectionException) {
            return false;
        } catch (\Throwable) {
            return false;
        }
    }
}
