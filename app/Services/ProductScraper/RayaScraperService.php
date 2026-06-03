<?php

namespace App\Services\ProductScraper;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class RayaScraperService
{
    protected string $graphqlUrl;

    protected string $shopUrl;

    protected string $storeCode;

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
        $config = config('product-scraper.raya');
        $this->graphqlUrl = rtrim($config['graphql_url'], '/');
        $this->shopUrl = rtrim($config['shop_url'], '/');
        $this->storeCode = $config['store_code'] ?? 'ar';
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
                    'message' => __('ecommerce.scrape_raya_unknown_collection', ['handle' => $handle]),
                ]);

                continue;
            }

            try {
                $batch = $this->fetchCollectionProducts($handle, $maxPerCollection)
                    ->map(fn (array $item) => $this->enrichFromProductDetail($item));

                $products = $products->merge($batch);
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
        $urlKey = $this->collectionParams[$handle]['url_key'] ?? null;

        if ($urlKey === null) {
            throw new \RuntimeException(__('ecommerce.scrape_raya_unknown_collection', ['handle' => $handle]));
        }

        $categoryName = $this->collections[$handle];
        $products = collect();
        $currentPage = 1;
        $pageSize = min(36, max($limit, 12));

        while ($products->count() < $limit) {
            $response = $this->graphql(
                <<<'GRAPHQL'
                query CategoryProducts($urlKey: String!, $pageSize: Int!, $currentPage: Int!) {
                    categoryList(filters: { url_key: { eq: $urlKey } }) {
                        products(pageSize: $pageSize, currentPage: $currentPage) {
                            total_count
                            items {
                                sku
                                name
                                url_key
                                stock_status
                                image { url }
                                price_range {
                                    minimum_price {
                                        final_price { value currency }
                                        regular_price { value currency }
                                    }
                                }
                            }
                        }
                    }
                }
                GRAPHQL,
                [
                    'urlKey' => $urlKey,
                    'pageSize' => $pageSize,
                    'currentPage' => $currentPage,
                ]
            );

            $items = data_get($response, 'data.categoryList.0.products.items', []);

            if (! is_array($items) || $items === []) {
                break;
            }

            foreach ($items as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $mapped = $this->mapListingItem($item, $handle, $categoryName);

                if ($mapped !== null) {
                    $products->push($mapped);
                }
            }

            $products = $products->unique('sku')->values();

            if (count($items) < $pageSize || $products->count() >= $limit) {
                break;
            }

            $currentPage++;
            $this->pause();
        }

        return $products->take($limit)->values();
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>|null
     */
    protected function mapListingItem(array $item, string $handle, string $categoryName): ?array
    {
        $vendorSku = trim((string) ($item['sku'] ?? ''));
        $name = trim((string) ($item['name'] ?? ''));
        $urlKey = trim((string) ($item['url_key'] ?? ''));

        if ($name === '' || $urlKey === '') {
            return null;
        }

        $finalPrice = (float) data_get($item, 'price_range.minimum_price.final_price.value', 0);
        $regularPrice = (float) data_get($item, 'price_range.minimum_price.regular_price.value', 0);

        if ($finalPrice <= 0 && $regularPrice <= 0) {
            return null;
        }

        if ($regularPrice <= 0) {
            $regularPrice = $finalPrice;
        }

        $discountPrice = null;
        if ($finalPrice > 0 && $regularPrice > $finalPrice) {
            $discountPrice = $finalPrice;
        } else {
            $finalPrice = $regularPrice;
        }

        $imageUrl = data_get($item, 'image.url');
        $imageUrl = is_string($imageUrl) && $imageUrl !== '' ? $imageUrl : null;

        $stockStatus = (string) ($item['stock_status'] ?? '');
        $sourceUrl = $this->shopUrl.'/'.$urlKey;

        return [
            'sku' => $this->makeSku($vendorSku),
            'name' => $name,
            'slug' => $urlKey,
            'category_name' => $categoryName,
            'category_slug' => 'raya-'.$handle,
            'parent_category_name' => $this->parentCategory['name'],
            'parent_category_slug' => $this->parentCategory['slug'],
            'short_description' => null,
            'full_description' => null,
            'main_image_url' => $imageUrl,
            'image_urls' => array_values(array_filter([$imageUrl])),
            'regular_price' => $regularPrice,
            'discount_price' => $discountPrice,
            'stock_quantity' => strtoupper($stockStatus) === 'IN_STOCK' ? 1 : 0,
            'source_url' => $sourceUrl,
            'vendor' => 'Raya Shop',
            'product_type' => $categoryName,
            'tags' => array_values(array_filter([
                $vendorSku,
                $urlKey,
            ])),
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    protected function enrichFromProductDetail(array $item): array
    {
        $urlKey = (string) ($item['slug'] ?? $item['tags'][1] ?? '');

        if ($urlKey === '') {
            return $item;
        }

        try {
            $response = $this->graphql(
                <<<'GRAPHQL'
                query ProductDetail($urlKey: String!) {
                    products(filter: { url_key: { eq: $urlKey } }) {
                        items {
                            sku
                            name
                            url_key
                            description { html }
                            short_description { html }
                            media_gallery { url label }
                            image { url }
                            price_range {
                                minimum_price {
                                    final_price { value }
                                    regular_price { value }
                                }
                            }
                        }
                    }
                }
                GRAPHQL,
                ['urlKey' => $urlKey]
            );
        } catch (\Throwable) {
            return $item;
        }

        $product = data_get($response, 'data.products.items.0');

        if (! is_array($product)) {
            return $item;
        }

        $detailUrlKey = (string) ($product['url_key'] ?? '');
        if ($detailUrlKey !== '' && $detailUrlKey !== $urlKey) {
            return $item;
        }

        if (! empty($product['sku'])) {
            $item['sku'] = $this->makeSku((string) $product['sku']);
        }

        if (! empty($product['name'])) {
            $item['name'] = (string) $product['name'];
        }

        $finalPrice = (float) data_get($product, 'price_range.minimum_price.final_price.value', 0);
        $regularPrice = (float) data_get($product, 'price_range.minimum_price.regular_price.value', 0);

        if ($regularPrice <= 0 && $finalPrice > 0) {
            $regularPrice = $finalPrice;
        }

        if ($regularPrice > 0) {
            if ($finalPrice > 0 && $regularPrice > $finalPrice) {
                $item['discount_price'] = $finalPrice;
                $item['regular_price'] = $regularPrice;
            } else {
                $item['regular_price'] = $regularPrice;
                $item['discount_price'] = null;
            }
        }

        $shortHtml = (string) data_get($product, 'short_description.html', '');
        $fullHtml = (string) data_get($product, 'description.html', '');

        if ($shortHtml !== '') {
            $plain = trim(preg_replace('/\s+/', ' ', strip_tags($shortHtml)) ?: '');
            $item['short_description'] = Str::limit($plain, 500);
        }

        if ($fullHtml !== '') {
            $item['full_description'] = $fullHtml;
            if ($item['short_description'] === null) {
                $plain = trim(preg_replace('/\s+/', ' ', strip_tags($fullHtml)) ?: '');
                $item['short_description'] = Str::limit($plain, 500);
            }
        }

        $gallery = collect($product['media_gallery'] ?? [])
            ->pluck('url')
            ->filter(fn ($url) => is_string($url) && $url !== '')
            ->values()
            ->all();

        $mainImage = data_get($product, 'image.url');
        if (is_string($mainImage) && $mainImage !== '') {
            $gallery = array_values(array_unique(array_merge([$mainImage], $gallery)));
        }

        if ($gallery !== []) {
            $item['image_urls'] = $gallery;
            $item['main_image_url'] = $gallery[0];
        }

        $this->pause();

        return $item;
    }

    protected function makeSku(string $vendorSku): string
    {
        $normalized = preg_replace('/[^\p{L}\p{N}\-_.]+/u', '-', trim($vendorSku)) ?: 'item';
        $normalized = trim(preg_replace('/-+/', '-', $normalized) ?: $normalized, '-');

        return $this->skuPrefix.$normalized;
    }

    /**
     * @param  array<string, mixed>  $variables
     * @return array<string, mixed>
     */
    protected function graphql(string $query, array $variables = []): array
    {
        $response = $this->http()->post($this->graphqlUrl, [
            'query' => $query,
            'variables' => $variables,
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException(__('ecommerce.scrape_raya_graphql_failed', [
                'status' => $response->status(),
            ]));
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new \RuntimeException(__('ecommerce.scrape_raya_graphql_invalid'));
        }

        if (! empty($payload['errors'])) {
            $message = data_get($payload, 'errors.0.message', 'GraphQL error');

            throw new \RuntimeException((string) $message);
        }

        return $payload;
    }

    protected function http(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withHeaders([
            'User-Agent' => $this->userAgent,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Store' => $this->storeCode,
        ])
            ->asJson()
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
            $response = $this->graphql(
                <<<'GRAPHQL'
                query Ping($urlKey: String!) {
                    categoryList(filters: { url_key: { eq: $urlKey } }) {
                        products(pageSize: 1, currentPage: 1) {
                            total_count
                        }
                    }
                }
                GRAPHQL,
                ['urlKey' => 'refrigerators']
            );

            return (int) data_get($response, 'data.categoryList.0.products.total_count', 0) > 0;
        } catch (ConnectionException) {
            return false;
        } catch (\Throwable) {
            return false;
        }
    }
}
