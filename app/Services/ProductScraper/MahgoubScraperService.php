<?php

namespace App\Services\ProductScraper;

use App\Support\DepartmentSubcategories;
use App\Support\MahgoubCategories;
use App\Support\SanitarySubcategories;
use App\Support\ScraperCollectionLabels;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MahgoubScraperService
{
    protected string $baseUrl;

    protected string $gridUrl;

    protected string $userAgent;

    protected int $connectTimeout;

    protected int $timeout;

    protected int $retryTimes;

    protected int $retrySleepMs;

    protected int $delayMs;

    protected string $skuPrefix;

    /** @var array{slug: string, name: string} */
    protected array $parentCategory;

    /** @var array<string, array{slug: string, name: string}> */
    protected array $parentCategories;

    /** @var array<string, string> */
    protected array $collectionParents;

    /** @var array<string, string> */
    protected array $collections;

    /** @var array<string, array<string, string>> */
    protected array $collectionParams;

    /** @var Collection<int, array{handle: string, message: string}> */
    protected Collection $scrapeErrors;

    public function __construct()
    {
        $config = config('product-scraper.mahgoub');
        $this->baseUrl = rtrim($config['base_url'], '/');
        $this->gridUrl = rtrim($config['grid_url'], '/');
        $this->userAgent = $config['user_agent'];
        $this->connectTimeout = (int) ($config['connect_timeout'] ?? 30);
        $this->timeout = (int) ($config['request_timeout'] ?? 90);
        $this->retryTimes = (int) ($config['retry_times'] ?? 3);
        $this->retrySleepMs = (int) ($config['retry_sleep_ms'] ?? 1500);
        $this->delayMs = (int) ($config['delay_ms'] ?? 300);
        $this->skuPrefix = $config['sku_prefix'];
        $this->parentCategory = $config['parent_category'];
        $this->parentCategories = $config['parent_categories'] ?? [];
        $this->collectionParents = $config['collection_parents'] ?? [];
        $this->collections = $config['collections'];
        $this->collectionParams = $config['collection_params'];
        $this->scrapeErrors = collect();
    }

    /** @return array<string, string> */
    public function getCollectionOptions(): array
    {
        $options = [];

        foreach ($this->collections as $handle => $name) {
            $parentKey = MahgoubCategories::parentSlugForHandle($handle);

            $options[$handle] = match ($parentKey) {
                'ceramics' => ScraperCollectionLabels::forDepartment(
                    [$handle => $name],
                    'ceramics',
                    DepartmentSubcategories::mahgoubCeramicsSubcategorySlug(...),
                )[$handle],
                'sanitary' => ScraperCollectionLabels::sanitary(
                    $handle,
                    $name,
                    SanitarySubcategories::mahgoubLeafSlug(...),
                ),
                default => $name,
            };
        }

        return $options;
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
                    'message' => __('ecommerce.scrape_mahgoub_unknown_collection', ['handle' => $handle]),
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
        $params = $this->collectionParams[$handle];
        $products = collect();
        $start = 0;
        $pageSize = min(24, max($limit, 1));

        while ($products->count() < $limit) {
            $response = $this->http()->get($this->gridUrl, array_merge($params, [
                'start' => $start,
                'sz' => $pageSize,
                'srule' => 'best-matches',
            ]));

            if (! $response->successful()) {
                throw new \RuntimeException(__('ecommerce.scrape_mahgoub_collection_failed', [
                    'handle' => $handle,
                    'status' => $response->status(),
                ]));
            }

            $batch = $this->parseGridHtml($response->body(), $handle);

            if ($batch->isEmpty()) {
                break;
            }

            $products = $products->merge($batch)->unique('sku')->values();

            if ($batch->count() < $pageSize) {
                break;
            }

            $start += $pageSize;
            $this->pause();
        }

        return $products->take($limit)->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function parseGridHtml(string $html, string $handle): Collection
    {
        $products = collect();

        if (! preg_match_all(
            '/<div class="product"[^>]*data-pid="([^"]+)"[^>]*data-gtmdata="([^"]+)"[\s\S]*?(?=<div class="product"|<\/div>\s*<\/div>\s*<\/div>\s*<\/div>\s*<\/div>\s*<\/div>|$)/u',
            $html,
            $matches,
            PREG_SET_ORDER
        )) {
            return $products;
        }

        foreach ($matches as $match) {
            $pid = $match[1];
            $gtmJson = html_entity_decode($match[2], ENT_QUOTES | ENT_HTML5);
            $chunk = $match[0];

            /** @var array<string, mixed>|null $gtm */
            $gtm = json_decode($gtmJson, true);

            if (! is_array($gtm)) {
                continue;
            }

            $name = trim((string) ($gtm['name'] ?? ''));
            $regularPrice = (float) ($gtm['price'] ?? 0);

            if ($name === '' || $regularPrice <= 0) {
                continue;
            }

            $href = $this->firstMatch($chunk, '/href="(\/ar\/[^"]+\.html)"/');
            $imageUrl = $this->firstMatch($chunk, '/<img class="tile-image" src="([^"]+)"/');
            $discountPrice = $this->resolveDiscountPrice($chunk, $regularPrice);
            $sourceUrl = $href !== null ? $this->baseUrl.$href : $this->baseUrl;
            $parentCategory = $this->resolveParentCategory($handle);
            $parentKey = MahgoubCategories::parentSlugForHandle($handle);
            $ceramicsSubSlug = $parentKey === 'ceramics'
                ? DepartmentSubcategories::mahgoubCeramicsSubcategorySlug($handle)
                : null;
            $sanitaryLeafSlug = $parentKey === 'sanitary'
                ? SanitarySubcategories::mahgoubLeafSlug($handle)
                : null;

            $row = [
                'sku' => $this->skuPrefix.$pid,
                'name' => $name,
                'slug' => $href !== null ? basename($href, '.html') : Str::slug($name),
                'category_name' => $this->categoryNameFor($handle),
                'category_slug' => $this->categorySlugFor($handle),
                'parent_category_name' => match (true) {
                    $ceramicsSubSlug !== null => DepartmentSubcategories::canonicalName('ceramics', $ceramicsSubSlug) ?? $parentCategory['name'],
                    $sanitaryLeafSlug !== null => SanitarySubcategories::name($sanitaryLeafSlug) ?? $parentCategory['name'],
                    default => $parentCategory['name'],
                },
                'parent_category_slug' => $ceramicsSubSlug ?? $sanitaryLeafSlug ?? $parentCategory['slug'],
                'short_description' => null,
                'full_description' => null,
                'main_image_url' => $imageUrl,
                'image_urls' => array_values(array_filter([$imageUrl])),
                'regular_price' => $regularPrice,
                'discount_price' => $discountPrice,
                'stock_quantity' => 1,
                'source_url' => $sourceUrl,
                'vendor' => trim((string) ($gtm['category'] ?? 'Mahgoub')),
                'product_type' => trim((string) ($gtm['category'] ?? '')),
                'tags' => array_values(array_filter([
                    trim((string) ($gtm['category'] ?? '')),
                ])),
            ];

            if ($ceramicsSubSlug !== null) {
                $row['grandparent_category_name'] = $this->parentCategories['ceramics']['name']
                    ?? $this->parentCategory['name'];
                $row['grandparent_category_slug'] = $this->parentCategories['ceramics']['slug']
                    ?? $this->parentCategory['slug'];
            } elseif ($sanitaryLeafSlug !== null) {
                $row['grandparent_category_name'] = $this->parentCategories['sanitary']['name'] ?? 'صحي';
                $row['grandparent_category_slug'] = $this->parentCategories['sanitary']['slug'] ?? 'sanitary';
            }

            $products->push($row);
        }

        return $products;
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
        $imageUrls = $this->extractCarouselImages($html);

        if ($imageUrls !== []) {
            $item['image_urls'] = array_values(array_unique(array_merge($imageUrls, $item['image_urls'] ?? [])));
            $item['main_image_url'] = $item['image_urls'][0];
        }

        $attributes = $this->extractAttributes($html);

        if ($attributes !== []) {
            $descriptionHtml = $this->buildDescriptionHtml($attributes);
            $plain = trim(preg_replace('/\s+/', ' ', strip_tags($descriptionHtml)) ?: '');

            $item['full_description'] = $descriptionHtml;
            $item['short_description'] = Str::limit($plain, 500);

            $globalCode = $attributes['الكود العالمي'] ?? null;
            if (is_string($globalCode) && $globalCode !== '') {
                $item['tags'] = array_values(array_unique(array_merge($item['tags'] ?? [], [$globalCode])));
            }
        }

        $this->pause();

        return $item;
    }

    /**
     * @return array<int, string>
     */
    protected function extractCarouselImages(string $html): array
    {
        preg_match_all(
            '/<div class=[\'"]carousel-item[^\'"]*[\'"][\s\S]*?<img src="([^"]+)"/u',
            $html,
            $matches
        );

        return array_values(array_unique(array_filter($matches[1] ?? [])));
    }

    /**
     * @return array<string, string>
     */
    protected function extractAttributes(string $html): array
    {
        preg_match_all(
            '/<li class="attribute-values">\s*([^:<]+):\s*([\s\S]*?)<\/li>/u',
            $html,
            $matches,
            PREG_SET_ORDER
        );

        $attributes = [];

        foreach ($matches as $match) {
            $label = trim(strip_tags($match[1]));
            $value = trim(strip_tags($match[2]));

            if ($label !== '' && $value !== '') {
                $attributes[$label] = $value;
            }
        }

        return $attributes;
    }

    /**
     * @param  array<string, string>  $attributes
     */
    protected function buildDescriptionHtml(array $attributes): string
    {
        $items = implode('', array_map(
            fn (string $label, string $value) => '<li>'.e("{$label}: {$value}").'</li>',
            array_keys($attributes),
            array_values($attributes)
        ));

        return '<ul>'.$items.'</ul>';
    }

    protected function resolveDiscountPrice(string $chunk, float $regularPrice): ?float
    {
        $sale = $this->firstMatch($chunk, '/<p class="sales">\s*<span class="value[^"]*" content="([0-9.]+)"/');

        if ($sale === null) {
            return null;
        }

        $salePrice = (float) $sale;

        if ($salePrice > 0 && $salePrice < $regularPrice) {
            return $salePrice;
        }

        return null;
    }

    protected function firstMatch(string $subject, string $pattern): ?string
    {
        if (preg_match($pattern, $subject, $matches)) {
            return $matches[1];
        }

        return null;
    }

    protected function categorySlugFor(string $handle): string
    {
        return (string) ($this->collectionParams[$handle]['category_slug'] ?? $handle);
    }

    protected function categoryNameFor(string $handle): string
    {
        $slug = $this->categorySlugFor($handle);

        return MahgoubCategories::canonicalName($slug)
            ?? $this->collections[$handle];
    }

    /**
     * @return array{slug: string, name: string}
     */
    protected function resolveParentCategory(string $handle): array
    {
        $parentKey = $this->collectionParents[$handle] ?? 'ceramics';

        return $this->parentCategories[$parentKey] ?? $this->parentCategory;
    }

    protected function http(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withHeaders([
            'User-Agent' => $this->userAgent,
            'Accept' => 'text/html,application/xhtml+xml,application/json',
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
            $params = $this->collectionParams['all'] ?? ['cgid' => 'a0Y4K000003uF2jUAE'];
            $response = $this->http()->get($this->gridUrl, array_merge($params, [
                'start' => 0,
                'sz' => 1,
                'srule' => 'best-matches',
            ]));

            return $response->successful() && str_contains($response->body(), 'data-pid=');
        } catch (ConnectionException) {
            return false;
        } catch (\Throwable) {
            return false;
        }
    }
}
