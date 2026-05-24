<?php

namespace Tests\Feature;

use App\Services\ProductScraper\HansScraperService;
use App\Services\ProductScraper\ScrapedProductImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HansProductScrapeTest extends TestCase
{
    use RefreshDatabase;

    public function test_scrapes_products_from_shopify_json(): void
    {
        Http::fake([
            'https://hansegypt.com/ar/collections/sinks/products.json*' => Http::response(
                file_get_contents(__DIR__.'/../fixtures/hans-products.json'),
                200
            ),
        ]);

        $items = app(HansScraperService::class)->scrapeCollections(['sinks'], 5);

        $this->assertCount(1, $items);
        $this->assertSame('HANS-DJIS 850p', $items->first()['sku']);
        $this->assertStringContainsString('دي جي آي إس 850p', $items->first()['name']);
        $this->assertSame('HANS — مصارف', $items->first()['category_name']);
        $this->assertSame('hans-sinks', $items->first()['category_slug']);
        $this->assertSame('accessories', $items->first()['parent_category_slug']);
        $this->assertSame(12590.0, $items->first()['regular_price']);
        $this->assertGreaterThanOrEqual(2, count($items->first()['image_urls']));
    }

    public function test_continues_when_one_collection_fails(): void
    {
        $fixture = file_get_contents(__DIR__.'/../fixtures/hans-products.json');

        Http::fake(function ($request) use ($fixture) {
            if (str_contains($request->url(), 'collections/ovens/products.json')) {
                throw new \Illuminate\Http\Client\ConnectionException('DNS timeout');
            }

            if (str_contains($request->url(), 'products.json')) {
                return Http::response($fixture, 200);
            }

            return Http::response([], 404);
        });

        $scraper = app(HansScraperService::class);
        $items = $scraper->scrapeCollections(['sinks', 'ovens'], 5);

        $this->assertCount(1, $items);
        $this->assertCount(1, $scraper->getScrapeErrors());
        $this->assertSame('ovens', $scraper->getScrapeErrors()->first()['handle']);
    }

    public function test_imports_scraped_products_with_categories(): void
    {
        Storage::fake('public');

        Http::fake(function ($request) {
            if (str_contains($request->url(), 'products.json')) {
                return Http::response(
                    file_get_contents(__DIR__.'/../fixtures/hans-products.json'),
                    200
                );
            }

            return Http::response('fake-image', 200, ['Content-Type' => 'image/jpeg']);
        });

        $items = app(HansScraperService::class)->scrapeCollections(['sinks'], 5);
        $importer = app(ScrapedProductImporter::class);
        $importer->import($items, true);

        $this->assertDatabaseHas('categories', ['slug' => 'accessories', 'name' => 'إكسسوارات']);
        $this->assertDatabaseHas('categories', ['slug' => 'hans-sinks', 'name' => 'HANS — مصارف']);
        $this->assertDatabaseHas('products', ['sku' => 'HANS-DJIS 850p']);
        $this->assertSame(1, $importer->getCreatedCount());
    }
}
