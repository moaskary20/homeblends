<?php

namespace Tests\Feature;

use App\Services\ProductScraper\GemmaScraperService;
use App\Services\ProductScraper\ScrapedProductImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GemmaProductScrapeTest extends TestCase
{
    use RefreshDatabase;

    public function test_scrapes_products_from_gemma_api(): void
    {
        $fixture = file_get_contents(__DIR__.'/../fixtures/gemma-shop.json');

        Http::fake([
            'https://api.gemma.qpix.io/model/shop*' => Http::response($fixture, 200),
        ]);

        $items = app(GemmaScraperService::class)->scrapeCollections(['wall-ceramic'], 5);

        $this->assertCount(1, $items);
        $this->assertSame('GEMMA-12-00-004-0665-000-', $items->first()['sku']);
        $this->assertStringContainsString('Mallorca', $items->first()['name']);
        $this->assertSame('Gemma — سيراميك حائط', $items->first()['category_name']);
        $this->assertSame('gemma-wall-ceramic', $items->first()['category_slug']);
        $this->assertSame('ceramics', $items->first()['parent_category_slug']);
        $this->assertSame(147.003, $items->first()['regular_price']);
        $this->assertGreaterThanOrEqual(2, count($items->first()['image_urls']));
    }

    public function test_continues_when_one_collection_fails(): void
    {
        $fixture = file_get_contents(__DIR__.'/../fixtures/gemma-shop.json');

        Http::fake(function ($request) use ($fixture) {
            if (str_contains($request->url(), 'type=5b61afd6b9b6e33aeffe7cc6')) {
                throw new \Illuminate\Http\Client\ConnectionException('DNS timeout');
            }

            if (str_contains($request->url(), 'api.gemma.qpix.io/model/shop')) {
                return Http::response($fixture, 200);
            }

            return Http::response([], 404);
        });

        $scraper = app(GemmaScraperService::class);
        $items = $scraper->scrapeCollections(['wall-ceramic', 'floor'], 5);

        $this->assertCount(1, $items);
        $this->assertCount(1, $scraper->getScrapeErrors());
        $this->assertSame('floor', $scraper->getScrapeErrors()->first()['handle']);
    }

    public function test_imports_scraped_gemma_products_with_categories(): void
    {
        Storage::fake('public');

        $fixture = file_get_contents(__DIR__.'/../fixtures/gemma-shop.json');

        Http::fake(function ($request) use ($fixture) {
            if (str_contains($request->url(), 'api.gemma.qpix.io/model/shop')) {
                return Http::response($fixture, 200);
            }

            return Http::response('fake-image', 200, ['Content-Type' => 'image/jpeg']);
        });

        $items = app(GemmaScraperService::class)->scrapeCollections(['wall-ceramic'], 5);
        $importer = app(ScrapedProductImporter::class);
        $importer->import($items, true);

        $this->assertDatabaseHas('categories', ['slug' => 'ceramics', 'name' => 'سيراميك']);
        $this->assertDatabaseHas('categories', ['slug' => 'gemma-wall-ceramic', 'name' => 'Gemma — سيراميك حائط']);
        $this->assertDatabaseHas('products', ['sku' => 'GEMMA-12-00-004-0665-000-']);
        $this->assertSame(1, $importer->getCreatedCount());
    }
}
