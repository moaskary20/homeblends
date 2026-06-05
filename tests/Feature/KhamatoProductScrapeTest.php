<?php

namespace Tests\Feature;

use App\Services\ProductScraper\KhamatoScraperService;
use App\Services\ProductScraper\ScrapedProductImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KhamatoProductScrapeTest extends TestCase
{
    use RefreshDatabase;

    public function test_scrapes_products_from_khamato_category_and_api(): void
    {
        $categoryHtml = file_get_contents(__DIR__.'/../fixtures/khamato-category.html');
        $productApi = file_get_contents(__DIR__.'/../fixtures/khamato-product-api.json');

        Http::fake(function ($request) use ($categoryHtml, $productApi) {
            if (str_contains($request->url(), 'doors-and-kitchen-hardware/door-accessories')) {
                return Http::response($categoryHtml, 200, ['Content-Type' => 'text/html']);
            }

            if (str_contains($request->url(), '/web-api/products/6633')) {
                return Http::response($productApi, 200, ['Content-Type' => 'application/json']);
            }

            if (str_contains($request->url(), '/web-api/products/6564')) {
                return Http::response(['data' => null], 200);
            }

            return Http::response('', 404);
        });

        $items = app(KhamatoScraperService::class)->scrapeCollections(['door-accessories'], 5);

        $this->assertCount(1, $items);
        $this->assertSame('KHAMATO-05006316-X', $items->first()['sku']);
        $this->assertStringContainsString('غيدينى', $items->first()['name']);
        $this->assertSame('اكسسوارات الابواب', $items->first()['category_name']);
        $this->assertSame('khamato-door-accessories', $items->first()['category_slug']);
        $this->assertSame('accessories', $items->first()['parent_category_slug']);
        $this->assertSame(12956.9, $items->first()['regular_price']);
        $this->assertSame(11779.0, $items->first()['discount_price']);
        $this->assertGreaterThanOrEqual(1, count($items->first()['image_urls']));
    }

    public function test_continues_when_one_collection_fails(): void
    {
        $categoryHtml = file_get_contents(__DIR__.'/../fixtures/khamato-category.html');

        Http::fake(function ($request) use ($categoryHtml) {
            if (str_contains($request->url(), 'furniture-accessories')) {
                throw new \Illuminate\Http\Client\ConnectionException('timeout');
            }

            if (str_contains($request->url(), 'door-accessories')) {
                return Http::response($categoryHtml, 200, ['Content-Type' => 'text/html']);
            }

            if (str_contains($request->url(), '/web-api/products/6633')) {
                return Http::response(
                    file_get_contents(__DIR__.'/../fixtures/khamato-product-api.json'),
                    200,
                    ['Content-Type' => 'application/json']
                );
            }

            return Http::response('', 404);
        });

        $scraper = app(KhamatoScraperService::class);
        $items = $scraper->scrapeCollections(['door-accessories', 'furniture-accessories'], 5);

        $this->assertCount(1, $items);
        $this->assertCount(1, $scraper->getScrapeErrors());
        $this->assertSame('furniture-accessories', $scraper->getScrapeErrors()->first()['handle']);
    }

    public function test_imports_scraped_khamato_products_with_categories(): void
    {
        Storage::fake('public');
        $categoryHtml = file_get_contents(__DIR__.'/../fixtures/khamato-category.html');
        $productApi = file_get_contents(__DIR__.'/../fixtures/khamato-product-api.json');

        Http::fake(function ($request) use ($categoryHtml, $productApi) {
            if (str_contains($request->url(), 'door-accessories')) {
                return Http::response($categoryHtml, 200, ['Content-Type' => 'text/html']);
            }

            if (str_contains($request->url(), '/web-api/products/6633')) {
                return Http::response($productApi, 200, ['Content-Type' => 'application/json']);
            }

            if (str_contains($request->url(), 'e-motion-cdn')) {
                return Http::response('fake-image', 200, ['Content-Type' => 'image/png']);
            }

            return Http::response('', 404);
        });

        $items = app(KhamatoScraperService::class)->scrapeCollections(['door-accessories'], 5);
        $importer = app(ScrapedProductImporter::class);
        $importer->import($items, true);

        $this->assertDatabaseHas('categories', ['slug' => 'accessories', 'name' => 'إكسسوارات']);
        $this->assertDatabaseHas('categories', ['slug' => 'khamato-door-accessories', 'name' => 'اكسسوارات الابواب']);
        $this->assertDatabaseHas('products', ['sku' => 'KHAMATO-05006316-X']);
        $this->assertSame(1, $importer->getCreatedCount());
    }

    public function test_scrapes_sanitary_products_under_sanitary_parent(): void
    {
        $categoryHtml = file_get_contents(__DIR__.'/../fixtures/khamato-category.html');
        $productApi = file_get_contents(__DIR__.'/../fixtures/khamato-product-api.json');

        Http::fake(function ($request) use ($categoryHtml, $productApi) {
            if (str_contains($request->url(), 'sanitary/basin-mixers')) {
                return Http::response($categoryHtml, 200, ['Content-Type' => 'text/html']);
            }

            if (str_contains($request->url(), '/web-api/products/6633')) {
                return Http::response($productApi, 200, ['Content-Type' => 'application/json']);
            }

            return Http::response('', 404);
        });

        $items = app(KhamatoScraperService::class)->scrapeCollections(['basin-mixers'], 5);

        $this->assertCount(1, $items);
        $this->assertSame('خلاطات أحواض', $items->first()['category_name']);
        $this->assertSame('khamato-basin-mixers', $items->first()['category_slug']);
        $this->assertSame('sanitary', $items->first()['parent_category_slug']);
        $this->assertSame('صحي', $items->first()['parent_category_name']);
    }
}
