<?php

namespace App\Services\ProductScraper;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CleopatraScraperService
{
    protected string $storeUrl;

    protected string $catalogUrl;

    protected string $apiUrl;

    protected string $wcStoreUrl;

    protected string $userAgent;

    protected int $connectTimeout;

    protected int $timeout;

    protected int $retryTimes;

    protected int $retrySleepMs;

    protected int $delayMs;

    protected string $skuPrefix;

    /** @var array<string, string> */
    protected array $collections;

    /** @var array<string, array{taxonomy: string, term_id: int}> */
    protected array $collectionParams;

    /** @var Collection<int, array<string, mixed>>|null */
    protected ?Collection $allCollections = null;

    /** @var Collection<int, array{handle: string, message: string}> */
    protected Collection $scrapeErrors;

    public function __construct()
    {
        $config = config('product-scraper.cleopatra');
        $this->storeUrl = rtrim($config['store_url'], '/');
        $this->catalogUrl = rtrim($config['catalog_url'], '/');
        $this->apiUrl = rtrim($config['api_url'], '/');
        $this->wcStoreUrl = rtrim($config['wc_store_url'], '/');
        $this->userAgent = $config['user_agent'];
        $this->connectTimeout = (int) ($config['connect_timeout'] ?? 30);
        $this->timeout = (int) ($config['request_timeout'] ?? 90);
        $this->retryTimes = (int) ($config['retry_times'] ?? 3);
        $this->retrySleepMs = (int) ($config['retry_sleep_ms'] ?? 1500);
        $this->delayMs = (int) ($config['delay_ms'] ?? 500);
        $this->skuPrefix = $config['sku_prefix'];
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

        try {
            $this->allCollections = $this->fetchAllCollections();
        } catch (\Throwable $e) {
            $this->scrapeErrors->push([
                'handle' => 'catalogue',
                'message' => $e->getMessage(),
            ]);

            return $products;
        }

        foreach ($collectionHandles as $handle) {
            if (! isset($this->collections[$handle], $this->collectionParams[$handle])) {
                $this->scrapeErrors->push([
                    'handle' => $handle,
                    'message' => __('ecommerce.scrape_cleopatra_unknown_collection', ['handle' => $handle]),
                ]);

                continue;
            }

            $categoryName = $this->collections[$handle];
            $params = $this->collectionParams[$handle];

            try {
                $matching = $this->matchingDesignCollections($params);

                $importedForHandle = 0;

                foreach ($matching as $designCollection) {
                    if ($importedForHandle >= $maxPerCollection) {
                        break;
                    }

                    $batch = $this->fetchProductsForDesignCollection(
                        $designCollection,
                        $handle,
                        $categoryName,
                        $maxPerCollection - $importedForHandle
                    );

                    $importedForHandle += $batch->count();
                    $products = $products->merge($batch);

                    $this->pause();
                }
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
    protected function fetchAllCollections(): Collection
    {
        if ($this->allCollections !== null) {
            return $this->allCollections;
        }

        $response = $this->http()->get("{$this->apiUrl}/list_collections");

        if (! $response->successful()) {
            throw new \RuntimeException(__('ecommerce.scrape_cleopatra_collection_failed', [
                'handle' => 'list_collections',
                'status' => $response->status(),
            ]));
        }

        $items = $response->json();

        if (! is_array($items)) {
            throw new \RuntimeException(__('ecommerce.scrape_cleopatra_invalid_response'));
        }

        $this->allCollections = collect($items);

        return $this->allCollections;
    }

    /**
     * @param  array<string, mixed>  $params
     * @return Collection<int, array<string, mixed>>
     */
    protected function matchingDesignCollections(array $params): Collection
    {
        if (isset($params['collection_id'])) {
            $collectionId = (int) $params['collection_id'];
            $match = $this->allCollections->first(fn (array $collection) => (int) ($collection['id'] ?? 0) === $collectionId);

            if ($match !== null) {
                return collect([$match]);
            }

            return collect([[
                'id' => $collectionId,
                'post_title' => '',
                'image' => '',
            ]]);
        }

        return $this->allCollections
            ->filter(fn (array $collection) => $this->matchesFilter($collection, $params))
            ->values();
    }

    /**
     * @param  array<string, mixed>  $collection
     * @param  array<string, mixed>  $params
     */
    protected function matchesFilter(array $collection, array $params): bool
    {
        if (isset($params['collection_id'])) {
            return (int) ($collection['id'] ?? 0) === (int) $params['collection_id'];
        }

        $taxonomy = (string) ($params['taxonomy'] ?? '');
        $termId = (int) ($params['term_id'] ?? 0);
        $filters = $collection['filters_data'] ?? [];

        foreach ([$filters, $filters['filters_on_collection'] ?? []] as $block) {
            if (! is_array($block)) {
                continue;
            }

            $termIds = data_get($block, "{$taxonomy}.term_id");

            if (is_array($termIds) && in_array($termId, array_map('intval', $termIds), true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $designCollection
     * @return Collection<int, array<string, mixed>>
     */
    protected function fetchProductsForDesignCollection(
        array $designCollection,
        string $handle,
        string $categoryName,
        int $limit
    ): Collection {
        $collectionId = (int) ($designCollection['id'] ?? 0);

        if ($collectionId <= 0) {
            return collect();
        }

        $response = $this->http()->get("{$this->apiUrl}/collection_data/{$collectionId}");

        if (! $response->successful()) {
            throw new \RuntimeException(__('ecommerce.scrape_cleopatra_collection_failed', [
                'handle' => (string) ($designCollection['post_title'] ?? $collectionId),
                'status' => $response->status(),
            ]));
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new \RuntimeException(__('ecommerce.scrape_cleopatra_invalid_response'));
        }

        $designTitle = (string) ($payload['title'] ?? $designCollection['post_title'] ?? '');
        $collectionImage = (string) ($payload['image'] ?? $designCollection['image'] ?? '');
        $mapped = collect();

        foreach ($payload['products'] ?? [] as $group) {
            foreach ($group['products'] ?? [] as $product) {
                if ($mapped->count() >= $limit) {
                    break 2;
                }

                if (! is_array($product)) {
                    continue;
                }

                $item = $this->mapCatalogProduct($product, $handle, $categoryName, $designTitle, $collectionImage);

                if ($item === null || (float) ($item['regular_price'] ?? 0) <= 0) {
                    continue;
                }

                $mapped->push($item);
            }
        }

        return $mapped;
    }

    /**
     * @param  array<string, mixed>  $product
     * @return array<string, mixed>|null
     */
    protected function mapCatalogProduct(
        array $product,
        string $handle,
        string $categoryName,
        string $designTitle,
        string $collectionImage
    ): ?array {
        $productId = (int) ($product['product_id'] ?? 0);

        if ($productId <= 0) {
            return null;
        }

        $wcProduct = $this->fetchWcProduct($productId);

        if ($wcProduct === null) {
            return null;
        }

        $size = (string) data_get($product, 'clp_product_size_tax.label', '');
        $type = (string) data_get($product, 'clp_product_type_tax.label', '');
        $title = trim((string) ($product['title'] ?? $wcProduct['name'] ?? ''));
        $nameParts = array_filter([$designTitle, $title, $size !== '' ? $size : null, $type !== '' ? $type : null]);
        $name = implode(' — ', $nameParts);

        $imageUrls = $this->collectImageUrls($product, $wcProduct, $collectionImage);
        $prices = $wcProduct['prices'] ?? [];
        $regularPrice = $this->resolveRegularPrice($prices);
        $discountPrice = $this->resolveDiscountPrice($prices, $regularPrice);
        $permalink = (string) ($wcProduct['permalink'] ?? "{$this->storeUrl}/product/{$productId}/");
        $slug = basename(rtrim(parse_url($permalink, PHP_URL_PATH) ?: '', '/')) ?: Str::slug($name);

        $descriptionHtml = $this->buildDescription($product, $designTitle, $size, $type);
        $plain = trim(preg_replace('/\s+/', ' ', strip_tags($descriptionHtml)) ?: '');

        return [
            'sku' => $this->skuPrefix.$productId,
            'name' => $name,
            'slug' => $slug,
            'category_name' => $categoryName,
            'category_slug' => 'cleopatra-'.$handle,
            'parent_category_name' => 'Cleopatra',
            'parent_category_slug' => 'cleopatra',
            'grandparent_category_name' => config('product-scraper.cleopatra.parent_category.name'),
            'grandparent_category_slug' => config('product-scraper.cleopatra.parent_category.slug'),
            'short_description' => Str::limit($plain, 500),
            'full_description' => $descriptionHtml !== '' ? $descriptionHtml : null,
            'main_image_url' => $imageUrls[0] ?? null,
            'image_urls' => $imageUrls,
            'regular_price' => $regularPrice,
            'discount_price' => $discountPrice,
            'stock_quantity' => ($wcProduct['is_in_stock'] ?? false) ? 1 : 0,
            'source_url' => $permalink,
            'vendor' => 'Cleopatra Ceramics',
            'product_type' => $type,
            'tags' => array_values(array_filter([$designTitle, $size, $type])),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function fetchWcProduct(int $productId): ?array
    {
        $response = $this->http()->get("{$this->wcStoreUrl}/products/{$productId}");

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();

        return is_array($data) ? $data : null;
    }

    /**
     * @param  array<string, mixed>  $product
     * @param  array<string, mixed>  $wcProduct
     * @return array<int, string>
     */
    protected function collectImageUrls(array $product, array $wcProduct, string $collectionImage): array
    {
        $urls = [];

        foreach ([$product['image'] ?? null, $collectionImage] as $url) {
            if (is_string($url) && filter_var($url, FILTER_VALIDATE_URL)) {
                $urls[] = $url;
            }
        }

        foreach ($wcProduct['images'] ?? [] as $image) {
            $src = $image['src'] ?? null;
            if (is_string($src) && $src !== '') {
                $urls[] = $src;
            }
        }

        return array_values(array_unique($urls));
    }

    /**
     * @param  array<string, mixed>  $prices
     */
    protected function resolveRegularPrice(array $prices): float
    {
        $rangeMin = data_get($prices, 'price_range.min_amount');
        if ($rangeMin !== null && (float) $rangeMin > 0) {
            return (float) $rangeMin;
        }

        $regular = (float) ($prices['regular_price'] ?? 0);
        if ($regular > 0) {
            return $regular;
        }

        return (float) ($prices['price'] ?? 0);
    }

    /**
     * @param  array<string, mixed>  $prices
     */
    protected function resolveDiscountPrice(array $prices, float $regularPrice): ?float
    {
        if (! ($prices['on_sale'] ?? false)) {
            return null;
        }

        $sale = (float) ($prices['sale_price'] ?? 0);

        if ($sale > 0 && $sale < $regularPrice) {
            return $sale;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $product
     */
    protected function buildDescription(array $product, string $designTitle, string $size, string $type): string
    {
        $lines = array_filter([
            $designTitle !== '' ? __('ecommerce.scrape_cleopatra_collection_label', ['name' => $designTitle]) : null,
            $size !== '' ? __('ecommerce.scrape_cleopatra_size_label', ['size' => $size]) : null,
            $type !== '' ? __('ecommerce.scrape_cleopatra_type_label', ['type' => $type]) : null,
        ]);

        foreach ($product['features'] ?? [] as $feature) {
            if (! is_array($feature)) {
                continue;
            }

            $label = (string) ($feature['label'] ?? '');
            $value = (string) ($feature['value'] ?? '');

            if ($label !== '' && $value !== '') {
                $lines[] = "{$label}: {$value}";
            }
        }

        if ($lines === []) {
            return '';
        }

        $items = implode('', array_map(fn (string $line) => '<li>'.e($line).'</li>', $lines));

        return '<ul>'.$items.'</ul>';
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
            $response = $this->http()->get("{$this->apiUrl}/list_collections");

            return $response->successful() && is_array($response->json()) && count($response->json()) > 0;
        } catch (ConnectionException) {
            return false;
        }
    }
}
