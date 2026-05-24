<?php

namespace App\Services\ProductScraper;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SedarScraperService
{
    protected string $baseUrl;

    protected string $uploadsBase;

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

    /** @var Collection<int, array{handle: string, message: string}> */
    protected Collection $scrapeErrors;

    public function __construct()
    {
        $config = config('product-scraper.sedar');
        $this->baseUrl = rtrim($config['base_url'], '/');
        $this->uploadsBase = rtrim($config['uploads_base'], '/');
        $this->userAgent = $config['user_agent'];
        $this->connectTimeout = (int) ($config['connect_timeout'] ?? 30);
        $this->timeout = (int) ($config['request_timeout'] ?? 90);
        $this->retryTimes = (int) ($config['retry_times'] ?? 3);
        $this->retrySleepMs = (int) ($config['retry_sleep_ms'] ?? 1500);
        $this->delayMs = (int) ($config['delay_ms'] ?? 500);
        $this->skuPrefix = $config['sku_prefix'];
        $this->parentCategory = $config['parent_category'];
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
                    'message' => __('ecommerce.scrape_sedar_unknown_collection', ['handle' => $handle]),
                ]);

                continue;
            }

            $categoryName = $this->collections[$handle];

            try {
                $batch = $this->fetchCollectionProducts($handle, $maxPerCollection)
                    ->flatMap(fn (array $material) => $this->mapMaterialProducts($material, $handle, $categoryName))
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
    public function fetchCollectionProducts(string $handle, int $limit = 10): Collection
    {
        $listingPath = $this->resolveListingPath($handle);
        $materials = collect();
        $page = 1;
        $pageCount = 1;

        while ($materials->count() < $limit * 5 && $page <= $pageCount) {
            $html = $this->fetchListingPage($listingPath, $page);
            $pageProps = $this->parseNextPageProps($html);
            $materialBlock = data_get($pageProps, 'productsData.result.MATERIAL', []);
            $batch = collect($materialBlock['result'] ?? []);
            $pageCount = max(1, (int) ($materialBlock['page_count'] ?? 1));

            if ($batch->isEmpty()) {
                break;
            }

            $materials = $materials->merge($batch);
            $page++;
            $this->pause();
        }

        return $materials;
    }

    protected function resolveListingPath(string $handle): string
    {
        if ($handle === 'curtains-and-drapes' || str_contains($handle, '/')) {
            return $handle;
        }

        return "curtains-and-drapes/{$handle}";
    }

    protected function fetchListingPage(string $listingPath, int $page): string
    {
        $listingPath = trim($listingPath, '/');
        $url = $page <= 1
            ? "{$this->baseUrl}/{$listingPath}"
            : "{$this->baseUrl}/{$listingPath}?page={$page}";

        $response = $this->http()->get($url);

        if (! $response->successful()) {
            throw new \RuntimeException(__('ecommerce.scrape_sedar_page_failed', [
                'page' => $page,
                'status' => $response->status(),
            ]));
        }

        return $response->body();
    }

    /**
     * @return array<string, mixed>
     */
    protected function parseNextPageProps(string $html): array
    {
        if (! preg_match('/<script id="__NEXT_DATA__" type="application\/json">(.*?)<\/script>/s', $html, $matches)) {
            throw new \RuntimeException(__('ecommerce.scrape_sedar_no_data'));
        }

        $payload = json_decode($matches[1], true);

        if (! is_array($payload)) {
            throw new \RuntimeException(__('ecommerce.scrape_sedar_invalid_json'));
        }

        return $payload['props']['pageProps'] ?? [];
    }

    /**
     * @param  array<string, mixed>  $material
     * @return array<int, array<string, mixed>>
     */
    protected function mapMaterialProducts(array $material, string $collectionHandle, string $categoryName): array
    {
        $items = $material['items'] ?? [];

        if ($items === []) {
            return [$this->mapProduct($material, null, $collectionHandle, $categoryName)];
        }

        return array_map(
            fn (array $item) => $this->mapProduct($material, $item, $collectionHandle, $categoryName),
            $items
        );
    }

    /**
     * @param  array<string, mixed>  $material
     * @param  array<string, mixed>|null  $item
     * @return array<string, mixed>
     */
    protected function mapProduct(array $material, ?array $item, string $collectionHandle, string $categoryName): array
    {
        $title = trim((string) ($material['SFP_TITLE'] ?? $material['SPI_DESC'] ?? $material['DESCRIPTION'] ?? ''));
        $fabric = trim((string) ($material['SFI_DESC'] ?? ''));
        $color = trim((string) ($item['SII_COLOR_DESC'] ?? ''));
        $nameParts = array_values(array_filter([$title, $fabric, $color]));
        $name = implode(' — ', $nameParts);

        $regularPrice = $this->resolvePrice($material);
        $oldPrice = $this->resolveOldPrice($material, $regularPrice);
        $sku = $this->resolveSku($material, $item);
        $imageUrls = $this->collectImageUrls($material, $item);
        $descriptionHtml = (string) ($material['SPI_FEATURES'] ?? '');
        $plain = trim(preg_replace('/\s+/', ' ', strip_tags($descriptionHtml)) ?: '');

        $itemCode = (string) ($item['SII_CODE'] ?? $material['SFI_CODE'] ?? Str::random(8));
        $relativeUrl = trim((string) ($material['url'] ?? ''), '/');
        $sourceUrl = "{$this->baseUrl}/{$relativeUrl}/{$itemCode}";

        $inStock = strtoupper((string) ($item['STOCK_STATUS'] ?? $item['SII_STATUS'] ?? $material['SFI_STATUS'] ?? '')) === 'INSTOCK';

        return [
            'sku' => $sku,
            'name' => $name,
            'slug' => Str::slug($name !== '' ? $name : $sku),
            'category_name' => $categoryName,
            'category_slug' => 'sedar-'.$collectionHandle,
            'parent_category_name' => $this->parentCategory['name'],
            'parent_category_slug' => $this->parentCategory['slug'],
            'short_description' => Str::limit($plain !== '' ? $plain : $title, 500),
            'full_description' => $descriptionHtml !== '' ? $descriptionHtml : null,
            'main_image_url' => $imageUrls[0] ?? null,
            'image_urls' => $imageUrls,
            'regular_price' => $regularPrice,
            'discount_price' => $oldPrice,
            'stock_quantity' => $inStock ? 1 : 0,
            'source_url' => $sourceUrl,
            'vendor' => 'sedar',
            'product_type' => trim((string) ($material['SFI_MT_DESC'] ?? 'منسوجات')),
            'tags' => array_values(array_filter([
                $fabric !== '' ? $fabric : null,
                $color !== '' ? $color : null,
                trim((string) ($material['SFI_COLLECTION_DESC'] ?? '')) ?: null,
            ])),
        ];
    }

    /**
     * @param  array<string, mixed>  $material
     * @param  array<string, mixed>|null  $item
     */
    protected function resolveSku(array $material, ?array $item): string
    {
        $itemId = trim((string) ($item['SII_ITEM_ID'] ?? ''));
        if ($itemId !== '') {
            return $this->skuPrefix.str_replace(['/', ' '], '-', $itemId);
        }

        $code = trim((string) ($item['SII_CODE'] ?? $material['SFI_CODE'] ?? ''));

        return $this->skuPrefix.$code;
    }

    /**
     * @param  array<string, mixed>  $material
     */
    protected function resolvePrice(array $material): float
    {
        foreach (['PRICE', 'FROM_PRICE'] as $field) {
            $value = (float) ($material[$field] ?? 0);
            if ($value > 0) {
                return $value;
            }
        }

        return 0.0;
    }

    /**
     * @param  array<string, mixed>  $material
     */
    protected function resolveOldPrice(array $material, float $regularPrice): ?float
    {
        $old = (float) ($material['PRICE_OLD'] ?? $material['OLD_PRICE'] ?? 0);

        return $old > $regularPrice ? $old : null;
    }

    /**
     * @param  array<string, mixed>  $material
     * @param  array<string, mixed>|null  $item
     * @return array<int, string>
     */
    protected function collectImageUrls(array $material, ?array $item): array
    {
        $urls = [];

        if ($item !== null) {
            $desktop = trim((string) ($item['SII_IMAGE_PATH_DESKTOP'] ?? ''));
            if ($desktop !== '') {
                $urls[] = "{$this->uploadsBase}/item/hover/{$desktop}";
            }

            $thumb = trim((string) ($item['SII_THUMBNAIL_IMAGES'] ?? ''));
            if ($thumb !== '') {
                $urls[] = "{$this->uploadsBase}/item/desktop/{$thumb}";
            }

            foreach ($item['gallery'] ?? [] as $galleryItem) {
                $path = $galleryItem['SLI_IMAGE_PATH'] ?? null;
                if (is_string($path) && $path !== '') {
                    $urls[] = $path;
                }
            }
        }

        $materialImage = trim((string) ($material['SPI_IMAGE_PATH'] ?? ''));
        if ($materialImage !== '') {
            $urls[] = "{$this->uploadsBase}/product/{$materialImage}";
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
            $html = $this->fetchListingPage('curtains-and-drapes', 1);
            $pageProps = $this->parseNextPageProps($html);
            $materials = data_get($pageProps, 'productsData.result.MATERIAL.result', []);

            return is_array($materials) && count($materials) > 0;
        } catch (ConnectionException) {
            return false;
        } catch (\Throwable) {
            return false;
        }
    }
}
