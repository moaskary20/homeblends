<?php

namespace App\Services\ProductScraper;

use App\Support\HomeApplianceCategories;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ShaheenScraperService
{
    protected string $wcStoreUrl;

    protected string $shopUrl;

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

    /** @var array<string, array<string, mixed>> */
    protected array $collectionParams;

    /** @var Collection<int, array{handle: string, message: string}> */
    protected Collection $scrapeErrors;

    public function __construct()
    {
        $config = config('product-scraper.shaheen');
        $this->wcStoreUrl = rtrim($config['wc_store_url'], '/');
        $this->shopUrl = rtrim($config['shop_url'], '/');
        $this->userAgent = $config['user_agent'];
        $this->connectTimeout = (int) ($config['connect_timeout'] ?? 30);
        $this->timeout = (int) ($config['request_timeout'] ?? 90);
        $this->retryTimes = (int) ($config['retry_times'] ?? 3);
        $this->retrySleepMs = (int) ($config['retry_sleep_ms'] ?? 1500);
        $this->delayMs = (int) ($config['delay_ms'] ?? 300);
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
                    'message' => __('ecommerce.scrape_shaheen_unknown_collection', ['handle' => $handle]),
                ]);

                continue;
            }

            try {
                $products = $products->merge($this->fetchCollectionProducts($handle, $maxPerCollection));
            } catch (\Throwable $e) {
                $this->scrapeErrors->push([
                    'handle' => $handle,
                    'message' => $e->getMessage(),
                ]);
            }

            $this->pause();
        }

        return $products->unique('slug')->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function fetchCollectionProducts(string $handle, int $limit = 10): Collection
    {
        $categoryId = (int) ($this->collectionParams[$handle]['category_id'] ?? 0);

        if ($categoryId <= 0) {
            throw new \RuntimeException(__('ecommerce.scrape_shaheen_unknown_collection', ['handle' => $handle]));
        }

        $categoryName = $this->categoryNameFor($handle);
        $products = collect();
        $page = 1;
        $pageSize = min(36, max($limit, 12));

        while ($products->count() < $limit) {
            $response = $this->http()->get("{$this->wcStoreUrl}/products", [
                'category' => $categoryId,
                'per_page' => $pageSize,
                'page' => $page,
            ]);

            if (! $response->successful()) {
                throw new \RuntimeException(__('ecommerce.scrape_shaheen_collection_failed', [
                    'handle' => $handle,
                    'status' => $response->status(),
                ]));
            }

            $items = $response->json();

            if (! is_array($items) || $items === []) {
                break;
            }

            foreach ($items as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $mapped = $this->mapProduct($item, $handle, $categoryName);

                if ($mapped !== null) {
                    $products->push($mapped);
                }
            }

            $products = $products->unique('slug')->values();

            if (count($items) < $pageSize || $products->count() >= $limit) {
                break;
            }

            $page++;
            $this->pause();
        }

        return $products->take($limit)->values();
    }

    /**
     * @param  array<string, mixed>  $product
     * @return array<string, mixed>|null
     */
    protected function mapProduct(array $product, string $handle, string $categoryName): ?array
    {
        $name = trim((string) ($product['name'] ?? ''));

        if ($name === '') {
            return null;
        }

        $prices = $product['prices'] ?? [];
        $minorUnit = (int) ($prices['currency_minor_unit'] ?? 2);
        $divisor = 10 ** $minorUnit;
        $regularPrice = (float) ($prices['regular_price'] ?? 0) / $divisor;
        $salePrice = (float) ($prices['sale_price'] ?? 0) / $divisor;

        if ($regularPrice <= 0 && $salePrice <= 0) {
            return null;
        }

        if ($regularPrice <= 0) {
            $regularPrice = $salePrice;
        }

        $discountPrice = null;
        if ($salePrice > 0 && $regularPrice > $salePrice) {
            $discountPrice = $salePrice;
        }

        $productId = (string) ($product['id'] ?? '');
        $vendorSku = trim((string) ($product['sku'] ?? ''));
        $sku = $this->makeSku($vendorSku !== '' ? $vendorSku : $productId);

        $slug = trim((string) ($product['slug'] ?? '')) ?: Str::slug($name);
        $sourceUrl = (string) ($product['permalink'] ?? "{$this->shopUrl}/store/{$slug}/");

        $imageUrls = collect($product['images'] ?? [])
            ->pluck('src')
            ->filter(fn ($url) => is_string($url) && $url !== '')
            ->values()
            ->all();

        $shortHtml = (string) ($product['short_description'] ?? '');
        $fullHtml = (string) ($product['description'] ?? '');
        $shortDescription = null;

        if ($shortHtml !== '') {
            $plain = trim(preg_replace('/\s+/', ' ', strip_tags($shortHtml)) ?: '');
            $shortDescription = Str::limit($plain, 500);
        } elseif ($fullHtml !== '') {
            $plain = trim(preg_replace('/\s+/', ' ', strip_tags($fullHtml)) ?: '');
            $shortDescription = Str::limit($plain, 500);
        }

        $inStock = (bool) ($product['is_in_stock'] ?? true);

        return [
            'sku' => $sku,
            'name' => $name,
            'slug' => $slug,
            'category_name' => $categoryName,
            'category_slug' => $this->categorySlugFor($handle),
            'parent_category_name' => $this->parentCategory['name'],
            'parent_category_slug' => $this->parentCategory['slug'],
            'short_description' => $shortDescription,
            'full_description' => $fullHtml !== '' ? $fullHtml : null,
            'main_image_url' => $imageUrls[0] ?? null,
            'image_urls' => $imageUrls,
            'regular_price' => $regularPrice,
            'discount_price' => $discountPrice,
            'stock_quantity' => $inStock ? 1 : 0,
            'source_url' => $sourceUrl,
            'vendor' => 'Shaheen Egypt',
            'product_type' => $categoryName,
            'tags' => array_values(array_filter([
                $vendorSku,
                $productId,
            ])),
        ];
    }

    protected function makeSku(string $identifier): string
    {
        $normalized = preg_replace('/[^\p{L}\p{N}\-_.]+/u', '-', trim($identifier)) ?: 'item';
        $normalized = trim(preg_replace('/-+/', '-', $normalized) ?: $normalized, '-');

        return $this->skuPrefix.$normalized;
    }

    protected function categorySlugFor(string $handle): string
    {
        return (string) ($this->collectionParams[$handle]['category_slug'] ?? $handle);
    }

    protected function categoryNameFor(string $handle): string
    {
        $slug = $this->categorySlugFor($handle);

        return HomeApplianceCategories::canonicalName($slug)
            ?? $this->collections[$handle];
    }

    protected function http(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withHeaders([
            'User-Agent' => $this->userAgent,
            'Accept' => 'application/json',
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
            $categoryId = (int) ($this->collectionParams['refrigerators']['category_id'] ?? 80);
            $response = $this->http()->get("{$this->wcStoreUrl}/products", [
                'category' => $categoryId,
                'per_page' => 1,
            ]);

            return $response->successful() && is_array($response->json()) && $response->json() !== [];
        } catch (ConnectionException) {
            return false;
        } catch (\Throwable) {
            return false;
        }
    }
}
