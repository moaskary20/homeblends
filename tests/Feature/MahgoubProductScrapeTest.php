<?php

namespace Tests\Feature;

use App\Services\ProductScraper\MahgoubScraperService;
use App\Services\ProductScraper\ScrapedProductImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MahgoubProductScrapeTest extends TestCase
{
    use RefreshDatabase;

    public function test_scrapes_products_from_mahgoub_grid(): void
    {
        $gridHtml = file_get_contents(__DIR__.'/../fixtures/mahgoub-grid.html');
        $productHtml = file_get_contents(__DIR__.'/../fixtures/mahgoub-product.html');

        Http::fake(function ($request) use ($gridHtml, $productHtml) {
            if (str_contains($request->url(), 'Search-UpdateGrid')) {
                return Http::response($gridHtml, 200, ['Content-Type' => 'text/html']);
            }

            if (str_contains($request->url(), '1020008734.html')) {
                return Http::response($productHtml, 200, ['Content-Type' => 'text/html']);
            }

            if (str_contains($request->url(), 'mahgoubceramic.storage.googleapis.com')) {
                return Http::response('fake-image', 200, ['Content-Type' => 'image/jpeg']);
            }

            return Http::response('', 404);
        });

        $items = app(MahgoubScraperService::class)->scrapeCollections(['brand-rako'], 5);

        $this->assertGreaterThanOrEqual(1, $items->count());
        $this->assertSame('MAHGOUB-1020008734', $items->first()['sku']);
        $this->assertStringContainsString('هيدو', $items->first()['name']);
        $this->assertSame('Mahgoub — راك', $items->first()['category_name']);
        $this->assertSame('mahgoub-brand-rako', $items->first()['category_slug']);
        $this->assertSame('ceramics', $items->first()['parent_category_slug']);
        $this->assertSame(678.0, $items->first()['regular_price']);
        $this->assertGreaterThanOrEqual(2, count($items->first()['image_urls']));
        $this->assertStringContainsString('الشركة:', $items->first()['full_description'] ?? '');
    }

    public function test_continues_when_one_collection_fails(): void
    {
        Http::fake(function ($request) {
            if (str_contains($request->url(), 'Search-UpdateGrid') && str_contains($request->url(), 'prefv1')) {
                throw new \Illuminate\Http\Client\ConnectionException('timeout');
            }

            if (str_contains($request->url(), 'Search-UpdateGrid')) {
                return Http::response('', 404);
            }

            return Http::response('', 404);
        });

        $scraper = app(MahgoubScraperService::class);
        $items = $scraper->scrapeCollections(['brand-rako'], 5);

        $this->assertCount(0, $items);
        $this->assertCount(1, $scraper->getScrapeErrors());
        $this->assertSame('brand-rako', $scraper->getScrapeErrors()->first()['handle']);
    }

    public function test_imports_scraped_mahgoub_products_with_categories(): void
    {
        Storage::fake('public');
        $gridHtml = file_get_contents(__DIR__.'/../fixtures/mahgoub-grid.html');
        $productHtml = file_get_contents(__DIR__.'/../fixtures/mahgoub-product.html');

        Http::fake(function ($request) use ($gridHtml, $productHtml) {
            if (str_contains($request->url(), 'Search-UpdateGrid')) {
                return Http::response($gridHtml, 200, ['Content-Type' => 'text/html']);
            }

            if (str_contains($request->url(), '.html')) {
                return Http::response($productHtml, 200, ['Content-Type' => 'text/html']);
            }

            if (str_contains($request->url(), 'mahgoubceramic.storage.googleapis.com')) {
                return Http::response('fake-image', 200, ['Content-Type' => 'image/jpeg']);
            }

            return Http::response('', 404);
        });

        $items = app(MahgoubScraperService::class)->scrapeCollections(['brand-rako'], 1);
        $importer = app(ScrapedProductImporter::class);
        $importer->import($items, true);

        $this->assertDatabaseHas('categories', ['slug' => 'ceramics', 'name' => 'سيراميك']);
        $this->assertDatabaseHas('categories', ['slug' => 'mahgoub-brand-rako', 'name' => 'Mahgoub — راك']);
        $this->assertDatabaseHas('products', ['sku' => 'MAHGOUB-1020008734']);
        $this->assertSame(1, $importer->getCreatedCount());
    }

    public function test_scrapes_sanitary_products_under_sanitary_parent(): void
    {
        $gridHtml = file_get_contents(__DIR__.'/../fixtures/mahgoub-sanitary-grid.html');

        Http::fake(function ($request) use ($gridHtml) {
            if (str_contains($request->url(), 'Search-UpdateGrid')) {
                return Http::response($gridHtml, 200, ['Content-Type' => 'text/html']);
            }

            if (str_contains($request->url(), '.html')) {
                return Http::response(
                    file_get_contents(__DIR__.'/../fixtures/mahgoub-product.html'),
                    200,
                    ['Content-Type' => 'text/html']
                );
            }

            return Http::response('', 404);
        });

        $items = app(MahgoubScraperService::class)->scrapeCollections(['sanitary-fixtures'], 3);

        $this->assertGreaterThanOrEqual(1, $items->count());
        $this->assertSame('Mahgoub — أدوات صحية', $items->first()['category_name']);
        $this->assertSame('mahgoub-sanitary-fixtures', $items->first()['category_slug']);
        $this->assertSame('sanitary', $items->first()['parent_category_slug']);
        $this->assertSame('صحي', $items->first()['parent_category_name']);
    }
}
