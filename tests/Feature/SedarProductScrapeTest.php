<?php

namespace Tests\Feature;

use App\Services\ProductScraper\ScrapedProductImporter;
use App\Services\ProductScraper\SedarScraperService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SedarProductScrapeTest extends TestCase
{
    use RefreshDatabase;

    public function test_scrapes_products_from_sedar_listing(): void
    {
        $fixture = file_get_contents(__DIR__.'/../fixtures/sedar-listing.html');

        Http::fake([
            '*' => Http::response($fixture, 200),
        ]);

        $items = app(SedarScraperService::class)->scrapeCollections(['fabric-curtain-pinch-pleat'], 5);

        $this->assertCount(1, $items);
        $this->assertSame('SEDAR-14FAB-ALNK600-24', $items->first()['sku']);
        $this->assertStringContainsString('ALNK600', $items->first()['name']);
        $this->assertSame('ستائر بينش بليت', $items->first()['category_name']);
        $this->assertSame('sedar-fabric-curtain-pinch-pleat', $items->first()['category_slug']);
        $this->assertSame('textiles', $items->first()['parent_category_slug']);
        $this->assertSame(782.25, $items->first()['regular_price']);
        $this->assertSame(950.0, $items->first()['discount_price']);
        $this->assertNotEmpty($items->first()['image_urls']);
    }

    public function test_continues_when_one_collection_fails(): void
    {
        $fixture = file_get_contents(__DIR__.'/../fixtures/sedar-listing.html');

        Http::fake(function ($request) use ($fixture) {
            if (str_contains($request->url(), 'fabric-curtain-ripple-fold')) {
                throw new \Illuminate\Http\Client\ConnectionException('DNS timeout');
            }

            if (str_contains($request->url(), 'sedarglobal.com')) {
                return Http::response($fixture, 200);
            }

            return Http::response([], 404);
        });

        $scraper = app(SedarScraperService::class);
        $items = $scraper->scrapeCollections(['fabric-curtain-pinch-pleat', 'fabric-curtain-ripple-fold'], 5);

        $this->assertCount(1, $items);
        $this->assertCount(1, $scraper->getScrapeErrors());
        $this->assertSame('fabric-curtain-ripple-fold', $scraper->getScrapeErrors()->first()['handle']);
    }

    public function test_imports_scraped_sedar_products_with_categories(): void
    {
        Storage::fake('public');

        $fixture = file_get_contents(__DIR__.'/../fixtures/sedar-listing.html');

        Http::fake(function ($request) use ($fixture) {
            if (str_contains($request->url(), 'sedarglobal.com')) {
                return Http::response($fixture, 200);
            }

            return Http::response('fake-image', 200, ['Content-Type' => 'image/webp']);
        });

        $items = app(SedarScraperService::class)->scrapeCollections(['fabric-curtain-pinch-pleat'], 5);
        $importer = app(ScrapedProductImporter::class);
        $importer->import($items, true);

        $this->assertDatabaseHas('categories', ['slug' => 'textiles', 'name' => 'منسوجات']);
        $this->assertDatabaseHas('categories', ['slug' => 'sedar-fabric-curtain-pinch-pleat', 'name' => 'ستائر بينش بليت']);
        $this->assertDatabaseHas('products', ['sku' => 'SEDAR-14FAB-ALNK600-24']);
        $this->assertSame(1, $importer->getCreatedCount());
    }
}
