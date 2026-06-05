<?php

namespace Tests\Feature;

use App\Services\ProductScraper\CleopatraScraperService;
use App\Services\ProductScraper\ScrapedProductImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CleopatraProductScrapeTest extends TestCase
{
    use RefreshDatabase;

    protected function fixture(): array
    {
        return json_decode(
            file_get_contents(__DIR__.'/../fixtures/cleopatra-catalog.json'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    }

    public function test_scrapes_products_from_cleopatra_api(): void
    {
        $fixture = $this->fixture();

        Http::fake(function ($request) use ($fixture) {
            if (str_contains($request->url(), '/list_collections')) {
                return Http::response($fixture['collections'], 200);
            }

            if (str_contains($request->url(), '/collection_data/30723')) {
                return Http::response($fixture['collection_data'], 200);
            }

            if (str_contains($request->url(), '/wc/store/v1/products/30724')) {
                return Http::response($fixture['wc_product'], 200);
            }

            return Http::response([], 404);
        });

        $items = app(CleopatraScraperService::class)->scrapeCollections(['floor-and-wall'], 5);

        $this->assertCount(1, $items);
        $this->assertSame('CLEOPATRA-30724', $items->first()['sku']);
        $this->assertStringContainsString('Carrara', $items->first()['name']);
        $this->assertSame('أرضيات وحوائط', $items->first()['category_name']);
        $this->assertSame('cleopatra-floor-and-wall', $items->first()['category_slug']);
        $this->assertSame('cleopatra', $items->first()['parent_category_slug']);
        $this->assertSame('ceramics', $items->first()['grandparent_category_slug']);
        $this->assertSame(594.0, $items->first()['regular_price']);
        $this->assertGreaterThanOrEqual(2, count($items->first()['image_urls']));
    }

    public function test_continues_when_one_collection_fails(): void
    {
        $fixture = $this->fixture();

        Http::fake(function ($request) use ($fixture) {
            if (str_contains($request->url(), '/list_collections')) {
                return Http::response($fixture['collections'], 200);
            }

            if (str_contains($request->url(), '/collection_data/30723')) {
                throw new \Illuminate\Http\Client\ConnectionException('timeout');
            }

            return Http::response([], 404);
        });

        $scraper = app(CleopatraScraperService::class);
        $items = $scraper->scrapeCollections(['floor-and-wall', 'floor'], 5);

        $this->assertCount(0, $items);
        $this->assertCount(1, $scraper->getScrapeErrors());
        $this->assertSame('floor-and-wall', $scraper->getScrapeErrors()->first()['handle']);
    }

    public function test_imports_scraped_cleopatra_products_with_categories(): void
    {
        Storage::fake('public');
        $fixture = $this->fixture();

        Http::fake(function ($request) use ($fixture) {
            if (str_contains($request->url(), '/list_collections')) {
                return Http::response($fixture['collections'], 200);
            }

            if (str_contains($request->url(), '/collection_data/30723')) {
                return Http::response($fixture['collection_data'], 200);
            }

            if (str_contains($request->url(), '/wc/store/v1/products/30724')) {
                return Http::response($fixture['wc_product'], 200);
            }

            if (str_contains($request->url(), 'cleopatraceramics.store')) {
                return Http::response('fake-image', 200, ['Content-Type' => 'image/jpeg']);
            }

            return Http::response([], 404);
        });

        $items = app(CleopatraScraperService::class)->scrapeCollections(['floor-and-wall'], 5);
        $importer = app(ScrapedProductImporter::class);
        $importer->import($items, true);

        $this->assertDatabaseHas('categories', ['slug' => 'ceramics', 'name' => 'سيراميك']);
        $this->assertDatabaseHas('categories', ['slug' => 'cleopatra', 'name' => 'Cleopatra']);
        $this->assertDatabaseHas('categories', ['slug' => 'cleopatra-floor-and-wall', 'name' => 'أرضيات وحوائط']);
        $this->assertDatabaseHas('products', ['sku' => 'CLEOPATRA-30724']);
        $this->assertSame(1, $importer->getCreatedCount());
    }
}
