<?php

namespace App\Services\ProductScraper;

use App\Support\HomeApplianceCategories;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SallabScraperService
{
    protected string $baseUrl;

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
        $config = config('product-scraper.sallab');
        $this->baseUrl = rtrim($config['base_url'], '/');
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
                    'message' => __('ecommerce.scrape_sallab_unknown_collection', ['handle' => $handle]),
                ]);

                continue;
            }

            try {
                $batch = $this->fetchCollectionProducts($handle, $maxPerCollection)
                    ->map(fn (array $item) => $this->enrichFromProductPage($item));

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
        $path = $this->collectionParams[$handle]['path'] ?? null;

        if ($path === null) {
            throw new \RuntimeException(__('ecommerce.scrape_sallab_unknown_collection', ['handle' => $handle]));
        }

        $categoryName = $this->categoryNameFor($handle);
        $products = collect();
        $page = 1;
        $pageSize = min(36, max($limit, 12));

        while ($products->count() < $limit) {
            $query = $page === 1
                ? ['product_list_limit' => $pageSize]
                : ['p' => $page, 'product_list_limit' => $pageSize];

            $response = $this->http()->get($this->baseUrl.$path, $query);

            if (! $response->successful()) {
                throw new \RuntimeException(__('ecommerce.scrape_sallab_collection_failed', [
                    'handle' => $handle,
                    'status' => $response->status(),
                ]));
            }

            $batch = $this->parseCategoryHtml($response->body(), $handle, $categoryName);

            if ($batch->isEmpty()) {
                break;
            }

            $products = $products->merge($batch)->unique('sku')->values();

            if ($batch->count() < $pageSize) {
                break;
            }

            $page++;
            $this->pause();
        }

        return $products->take($limit)->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function parseCategoryHtml(string $html, string $handle, string $categoryName): Collection
    {
        $products = collect();

        if (! preg_match_all(
            '/<li class="item product product-item[^"]*"[^>]*>([\s\S]*?)<\/li>/u',
            $html,
            $matches,
            PREG_SET_ORDER
        )) {
            return $products;
        }

        foreach ($matches as $match) {
            $chunk = $match[1];
            $item = $this->mapListingItem($chunk, $handle, $categoryName);

            if ($item !== null) {
                $products->push($item);
            }
        }

        return $products;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function mapListingItem(string $chunk, string $handle, string $categoryName): ?array
    {
        $productId = $this->firstMatch($chunk, '/data-product-id="(\d+)"/')
            ?? $this->firstMatch($chunk, '/<input type="hidden" name="product" value="(\d+)"/');

        $vendorSku = $this->firstMatch($chunk, '/data-product-sku="([^"]+)"/');
        $name = trim(html_entity_decode(strip_tags($this->firstMatch($chunk, '/class="product-item-link"[^>]*>\s*([^<]+)/') ?? ''), ENT_QUOTES | ENT_HTML5));
        $href = $this->firstMatch($chunk, '/href="(https:\/\/www\.ahmedelsallab\.com[^"]+\.html)"/')
            ?? $this->firstMatch($chunk, '/href="(\/en\/[^"]+\.html)"/');

        if ($name === '' || $href === null) {
            return null;
        }

        $regularPrice = (float) ($this->firstMatch($chunk, '/data-price-amount="([0-9.]+)"/') ?? 0);

        if ($regularPrice <= 0) {
            $priceText = $this->firstMatch($chunk, '/<span class="price">([^<]+)/');
            $regularPrice = $this->parsePriceText($priceText);
        }

        if ($regularPrice <= 0) {
            return null;
        }

        $discountPrice = null;
        $oldPrice = (float) ($this->firstMatch($chunk, '/data-price-type="oldPrice"[^>]*data-price-amount="([0-9.]+)"/') ?? 0);
        if ($oldPrice > $regularPrice) {
            $discountPrice = $regularPrice;
            $regularPrice = $oldPrice;
        }

        $imageUrl = $this->firstMatch($chunk, '/class="product-image-photo[^"]*"[^>]*data-src="([^"]+)"/')
            ?? $this->firstMatch($chunk, '/class="product-image-photo[^"]*"[^>]*src="([^"]+)"/');

        if ($imageUrl !== null && str_starts_with($imageUrl, 'data:')) {
            $imageUrl = null;
        }

        $sourceUrl = str_starts_with($href, 'http') ? $href : $this->baseUrl.$href;
        $sku = $this->skuPrefix.($vendorSku ?: $productId);

        return [
            'sku' => $sku,
            'name' => $name,
            'slug' => basename(parse_url($sourceUrl, PHP_URL_PATH) ?: '', '.html') ?: Str::slug($name),
            'category_name' => $categoryName,
            'category_slug' => $this->categorySlugFor($handle),
            'parent_category_name' => $this->parentCategory['name'],
            'parent_category_slug' => $this->parentCategory['slug'],
            'short_description' => null,
            'full_description' => null,
            'main_image_url' => $imageUrl,
            'image_urls' => array_values(array_filter([$imageUrl])),
            'regular_price' => $regularPrice,
            'discount_price' => $discountPrice,
            'stock_quantity' => 1,
            'source_url' => $sourceUrl,
            'vendor' => 'Ahmed El Sallab',
            'product_type' => $categoryName,
            'tags' => array_values(array_filter([
                $vendorSku,
                $productId,
            ])),
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    protected function enrichFromProductPage(array $item): array
    {
        $sourceUrl = (string) ($item['source_url'] ?? '');

        if ($sourceUrl === '' || ! str_contains($sourceUrl, '.html')) {
            return $item;
        }

        try {
            $response = $this->http()->get($sourceUrl);
        } catch (\Throwable) {
            return $item;
        }

        if (! $response->successful()) {
            return $item;
        }

        $html = $response->body();
        $product = $this->extractJsonLdProduct($html);

        if ($product !== null) {
            if (! empty($product['sku'])) {
                $item['sku'] = $this->skuPrefix.$product['sku'];
            }

            if (! empty($product['name'])) {
                $item['name'] = (string) $product['name'];
            }

            $offerPrice = data_get($product, 'offers.0.price') ?? data_get($product, 'offers.price');
            if (is_numeric($offerPrice) && (float) $offerPrice > 0) {
                $item['regular_price'] = (float) $offerPrice;
            }

            $availability = (string) (data_get($product, 'offers.0.availability') ?? data_get($product, 'offers.availability') ?? '');
            $item['stock_quantity'] = str_contains($availability, 'OutOfStock') ? 0 : 1;

            if (! empty($product['description'])) {
                $plain = trim(preg_replace('/\s+/', ' ', strip_tags((string) $product['description'])) ?: '');
                $item['short_description'] = Str::limit($plain, 500);
                $item['full_description'] = (string) $product['description'];
            }

            $images = $product['image'] ?? null;
            if (is_string($images) && $images !== '') {
                $item['image_urls'] = array_values(array_unique(array_merge([$images], $item['image_urls'] ?? [])));
                $item['main_image_url'] = $item['image_urls'][0];
            } elseif (is_array($images) && $images !== []) {
                $item['image_urls'] = array_values(array_unique(array_merge($images, $item['image_urls'] ?? [])));
                $item['main_image_url'] = $item['image_urls'][0];
            }
        }

        $galleryImages = $this->extractGalleryImages($html);
        if ($galleryImages !== []) {
            $item['image_urls'] = array_values(array_unique(array_merge($galleryImages, $item['image_urls'] ?? [])));
            $item['main_image_url'] = $item['image_urls'][0];
        }

        $this->pause();

        return $item;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function extractJsonLdProduct(string $html): ?array
    {
        if (! preg_match_all('/<script type="application\/ld\+json">([\s\S]*?)<\/script>/u', $html, $matches)) {
            return null;
        }

        foreach ($matches[1] as $json) {
            try {
                $data = json_decode(html_entity_decode($json, ENT_QUOTES | ENT_HTML5), true, 512, JSON_THROW_ON_ERROR);
            } catch (\Throwable) {
                continue;
            }

            if (is_array($data) && ($data['@type'] ?? null) === 'Product') {
                return $data;
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    protected function extractGalleryImages(string $html): array
    {
        preg_match_all(
            '/(?:data-src|src)="(https:\/\/www\.ahmedelsallab\.com\/media\/catalog\/product[^"]+)"/u',
            $html,
            $matches
        );

        return array_values(array_unique(array_filter($matches[1] ?? [])));
    }

    protected function parsePriceText(?string $priceText): float
    {
        if ($priceText === null) {
            return 0.0;
        }

        $normalized = preg_replace('/[^\d.]/', '', str_replace(',', '', $priceText)) ?: '0';

        return (float) $normalized;
    }

    protected function firstMatch(string $subject, string $pattern): ?string
    {
        if (preg_match($pattern, $subject, $matches)) {
            return $matches[1];
        }

        return null;
    }

    protected function http(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withHeaders([
            'User-Agent' => $this->userAgent,
            'Accept' => 'text/html,application/xhtml+xml',
            'Accept-Language' => 'en-US,en;q=0.9,ar;q=0.8',
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

    public function ping(): bool
    {
        try {
            $path = $this->collectionParams['all']['path'] ?? '/en/home-appliances.html';
            $response = $this->http()->get($this->baseUrl.$path, ['product_list_limit' => 1]);

            return $response->successful() && str_contains($response->body(), 'product-item');
        } catch (ConnectionException) {
            return false;
        } catch (\Throwable) {
            return false;
        }
    }
}
