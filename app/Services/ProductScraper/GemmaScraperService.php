<?php

namespace App\Services\ProductScraper;

use App\Support\DepartmentSubcategories;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GemmaScraperService
{
    protected string $apiUrl;

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

    /** @var array<string, array<string, string>> */
    protected array $collectionParams;

    /** @var Collection<int, array{handle: string, message: string}> */
    protected Collection $scrapeErrors;

    public function __construct()
    {
        $config = config('product-scraper.gemma');
        $this->apiUrl = rtrim($config['api_url'], '/');
        $this->shopUrl = rtrim($config['shop_url'], '/');
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
            if (! isset($this->collections[$handle])) {
                $this->scrapeErrors->push([
                    'handle' => $handle,
                    'message' => __('ecommerce.scrape_gemma_unknown_collection', ['handle' => $handle]),
                ]);

                continue;
            }

            $categoryName = $this->collections[$handle];

            try {
                $batch = $this->fetchCollectionModels($handle, $maxPerCollection)
                    ->flatMap(fn (array $model) => $this->mapModelProducts($model, $handle, $categoryName))
                    ->take($maxPerCollection);

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
    public function fetchCollectionModels(string $handle, int $limit = 10): Collection
    {
        $params = array_merge(
            $this->collectionParams[$handle] ?? [],
            [
                'limit' => min(max($limit * 3, 10), 100),
                'skip' => 0,
            ]
        );

        $response = $this->http()->get("{$this->apiUrl}/model/shop", $params);

        if (! $response->successful()) {
            throw new \RuntimeException(__('ecommerce.scrape_gemma_collection_failed', [
                'handle' => $handle,
                'status' => $response->status(),
            ]));
        }

        $models = $response->json();

        if (! is_array($models)) {
            throw new \RuntimeException(__('ecommerce.scrape_gemma_invalid_response'));
        }

        return collect($models);
    }

    /**
     * @param  array<string, mixed>  $model
     * @return array<int, array<string, mixed>>
     */
    protected function mapModelProducts(array $model, string $collectionHandle, string $categoryName): array
    {
        $variants = $model['products'] ?? [];

        if ($variants === []) {
            return [$this->mapProduct($model, null, $collectionHandle, $categoryName)];
        }

        return array_map(
            fn (array $variant) => $this->mapProduct($model, $variant, $collectionHandle, $categoryName),
            $variants
        );
    }

    /**
     * @param  array<string, mixed>  $model
     * @param  array<string, mixed>|null  $variant
     * @return array<string, mixed>
     */
    protected function mapProduct(array $model, ?array $variant, string $collectionHandle, string $categoryName): array
    {
        $modelName = trim((string) ($model['name'] ?? ''));
        $variantName = trim((string) ($variant['name'] ?? ''));
        $name = $variantName !== '' && $variantName !== $modelName
            ? "{$modelName} — {$variantName}"
            : ($modelName !== '' ? $modelName : $variantName);

        $sku = $this->resolveSku($model, $variant);
        $imageUrls = $this->collectImageUrls($model, $variant);
        $regularPrice = $this->resolvePrice($variant ?? $model);
        $stockQuantity = $this->resolveStock($variant ?? $model);
        $description = $this->buildDescription($model, $variant);

        $productId = (string) ($variant['_id'] ?? $model['_id'] ?? '');

        return [
            'sku' => $sku,
            'name' => $name,
            'slug' => Str::slug($name !== '' ? $name : $sku),
            'category_name' => $categoryName,
            'category_slug' => 'gemma-'.$collectionHandle,
            'parent_category_name' => DepartmentSubcategories::canonicalName(
                'ceramics',
                DepartmentSubcategories::gemmaCeramicsSubcategorySlug($collectionHandle)
            ) ?? $categoryName,
            'parent_category_slug' => DepartmentSubcategories::gemmaCeramicsSubcategorySlug($collectionHandle),
            'grandparent_category_name' => $this->parentCategory['name'],
            'grandparent_category_slug' => $this->parentCategory['slug'],
            'short_description' => Str::limit($description, 500),
            'full_description' => $description !== '' ? $description : null,
            'main_image_url' => $imageUrls[0] ?? null,
            'image_urls' => $imageUrls,
            'regular_price' => $regularPrice,
            'discount_price' => null,
            'stock_quantity' => $stockQuantity,
            'source_url' => $productId !== '' ? "{$this->shopUrl}/single-product/{$productId}" : $this->shopUrl.'/shop',
            'vendor' => 'gemma',
            'product_type' => is_scalar($model['material'] ?? null)
                ? trim((string) $model['material'])
                : 'سيراميك',
            'tags' => array_values(array_filter([
                is_scalar($model['finish'] ?? null) ? trim((string) $model['finish']) : null,
                is_scalar($model['material'] ?? null) ? trim((string) $model['material']) : null,
                ...collect((array) ($model['style'] ?? []))
                    ->filter(fn ($style) => is_scalar($style))
                    ->map(fn ($style) => trim((string) $style))
                    ->filter()
                    ->all(),
            ])),
        ];
    }

    /**
     * @param  array<string, mixed>  $model
     * @param  array<string, mixed>|null  $variant
     */
    protected function resolveSku(array $model, ?array $variant): string
    {
        $segmentId = trim((string) ($variant['segmentId'] ?? ''));
        if ($segmentId !== '') {
            return $this->skuPrefix.str_replace(['.', ' '], '-', $segmentId);
        }

        $id = trim((string) ($variant['_id'] ?? $model['_id'] ?? Str::random(8)));

        return $this->skuPrefix.$id;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    protected function resolvePrice(array $item): float
    {
        $prices = $item['prices'] ?? [];
        if (! is_array($prices)) {
            return (float) ($item['minPrice'] ?? 0);
        }

        $values = collect($prices)
            ->only(['price_1', 'price_2', 'price_3'])
            ->map(fn ($value) => (float) $value)
            ->filter(fn (float $price) => $price > 0);

        if ($values->isNotEmpty()) {
            return (float) $values->min();
        }

        return (float) ($item['minPrice'] ?? 0);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    protected function resolveStock(array $item): int
    {
        $stockEntries = $item['stock'] ?? [];
        if (! is_array($stockEntries) || $stockEntries === []) {
            return ($item['active'] ?? true) ? 1 : 0;
        }

        $available = collect($stockEntries)
            ->sum(fn (array $entry) => max(0, (float) ($entry['available'] ?? 0)));

        return $available > 0 ? max(1, (int) floor($available)) : 0;
    }

    /**
     * @param  array<string, mixed>  $model
     * @param  array<string, mixed>|null  $variant
     */
    protected function buildDescription(array $model, ?array $variant): string
    {
        $size = $model['size'] ?? '';
        if (! is_scalar($size)) {
            $size = '';
        }

        $styles = collect((array) ($model['style'] ?? []))
            ->filter(fn ($style) => is_scalar($style) && trim((string) $style) !== '')
            ->map(fn ($style) => trim((string) $style))
            ->all();

        $parts = array_filter([
            is_scalar($model['material'] ?? null) && trim((string) $model['material']) !== ''
                ? 'المادة: '.$model['material'] : null,
            is_scalar($model['finish'] ?? null) && trim((string) $model['finish']) !== ''
                ? 'التشطيب: '.$model['finish'] : null,
            trim((string) $size) !== '' ? 'المقاس: '.$size : null,
            $styles !== [] ? 'الطراز: '.implode(', ', $styles) : null,
            is_scalar($variant['segmentId'] ?? null) && trim((string) ($variant['segmentId'] ?? '')) !== ''
                ? 'الكود: '.$variant['segmentId'] : null,
        ]);

        return implode(' | ', $parts);
    }

    /**
     * @param  array<string, mixed>  $model
     * @param  array<string, mixed>|null  $variant
     * @return array<int, string>
     */
    protected function collectImageUrls(array $model, ?array $variant): array
    {
        $urls = [];

        foreach ($variant['images'] ?? [] as $url) {
            if (is_string($url) && $url !== '') {
                $urls[] = $url;
            }
        }

        $featured = $variant['featured_image'] ?? null;
        if (is_string($featured) && $featured !== '') {
            $urls[] = $featured;
        }

        foreach ($model['images'] ?? [] as $url) {
            if (is_string($url) && $url !== '') {
                $urls[] = $url;
            }
        }

        return array_values(array_unique(array_filter($urls)));
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
            $response = $this->http()->get("{$this->apiUrl}/model/shop", ['limit' => 1]);

            return $response->successful() && is_array($response->json()) && count($response->json()) > 0;
        } catch (ConnectionException) {
            return false;
        } catch (\Throwable) {
            return false;
        }
    }
}
