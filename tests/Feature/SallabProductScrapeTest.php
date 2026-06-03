<?php

namespace Tests\Feature;

use App\Services\ProductScraper\SallabScraperService;
use App\Services\ProductScraper\ScrapedProductImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SallabProductScrapeTest extends TestCase
{
    use RefreshDatabase;

    public function test_scrapes_products_from_sallab_category_page(): void
    {
        $categoryHtml = file_get_contents(__DIR__.'/../fixtures/sallab-category.html');
        $productHtml = file_get_contents(__DIR__.'/../fixtures/sallab-product.html');

        Http::fake(function ($request) use ($categoryHtml, $productHtml) {
            if (str_contains($request->url(), 'fridges.html')) {
                return Http::response($categoryHtml, 200, ['Content-Type' => 'text/html']);
            }

            if (str_contains($request->url(), 'kgn36nl30u.html')) {
                return Http::response($productHtml, 200, ['Content-Type' => 'text/html']);
            }

            if (str_contains($request->url(), 'ahmedelsallab.com/media/')) {
                return Http::response('fake-image', 200, ['Content-Type' => 'image/jpeg']);
            }

            return Http::response('', 404);
        });

        $items = app(SallabScraperService::class)->scrapeCollections(['fridges'], 5);

        $this->assertGreaterThanOrEqual(1, $items->count());
        $this->assertSame('SALLAB-APBOS003236', $items->first()['sku']);
        $this->assertStringContainsString('Bosch Refrigerator', $items->first()['name']);
        $this->assertSame('Sallab — ثلاجات', $items->first()['category_name']);
        $this->assertSame('sallab-fridges', $items->first()['category_slug']);
        $this->assertSame('home-appliances', $items->first()['parent_category_slug']);
        $this->assertSame('الأجهزة المنزلية', $items->first()['parent_category_name']);
        $this->assertSame(31099.0, $items->first()['regular_price']);
    }

    public function test_imports_scraped_sallab_products_with_categories(): void
    {
        Storage::fake('public');
        $categoryHtml = file_get_contents(__DIR__.'/../fixtures/sallab-category.html');
        $productHtml = file_get_contents(__DIR__.'/../fixtures/sallab-product.html');

        Http::fake(function ($request) use ($categoryHtml, $productHtml) {
            if (str_contains($request->url(), 'fridges.html')) {
                return Http::response($categoryHtml, 200, ['Content-Type' => 'text/html']);
            }

            if (str_contains($request->url(), '.html')) {
                return Http::response($productHtml, 200, ['Content-Type' => 'text/html']);
            }

            if (str_contains($request->url(), 'ahmedelsallab.com/media/')) {
                return Http::response('fake-image', 200, ['Content-Type' => 'image/jpeg']);
            }

            return Http::response('', 404);
        });

        $items = app(SallabScraperService::class)->scrapeCollections(['fridges'], 1);
        $importer = app(ScrapedProductImporter::class);
        $importer->import($items, true);

        $this->assertDatabaseHas('categories', ['slug' => 'home-appliances', 'name' => 'الأجهزة المنزلية']);
        $this->assertDatabaseHas('categories', ['slug' => 'sallab-fridges', 'name' => 'Sallab — ثلاجات']);
        $this->assertDatabaseHas('products', ['sku' => 'SALLAB-APBOS003236']);
        $this->assertSame(1, $importer->getCreatedCount());
    }
}
