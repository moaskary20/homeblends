<?php

namespace Tests\Feature;

use App\Services\ProductScraper\ScrapedProductImporter;
use App\Services\ProductScraper\ShaheenScraperService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ShaheenProductScrapeTest extends TestCase
{
    use RefreshDatabase;

    public function test_scrapes_products_from_shaheen_wc_store_api(): void
    {
        $productsJson = json_decode(file_get_contents(__DIR__.'/../fixtures/shaheen-products.json'), true);

        Http::fake(function ($request) use ($productsJson) {
            if (str_contains($request->url(), 'shaheeneg.com/ar/wp-json/wc/store/v1/products')
                && $request->method() === 'GET') {
                return Http::response($productsJson, 200, ['Content-Type' => 'application/json']);
            }

            if (str_contains($request->url(), 'shaheeneg.com/wp-content/')) {
                return Http::response('fake-image', 200, ['Content-Type' => 'image/jpeg']);
            }

            return Http::response('', 404);
        });

        $items = app(ShaheenScraperService::class)->scrapeCollections(['refrigerators'], 5);

        $this->assertGreaterThanOrEqual(1, $items->count());
        $this->assertSame('SHAHEEN-RDNE430K12B', $items->first()['sku']);
        $this->assertStringContainsString('بيكو', $items->first()['name']);
        $this->assertSame('ثلاجات', $items->first()['category_name']);
        $this->assertSame('refrigerators', $items->first()['category_slug']);
        $this->assertSame('home-appliances', $items->first()['parent_category_slug']);
        $this->assertSame(45999.0, $items->first()['regular_price']);
        $this->assertSame(41000.0, $items->first()['discount_price']);
    }

    public function test_imports_scraped_shaheen_products_with_categories(): void
    {
        Storage::fake('public');
        $productsJson = json_decode(file_get_contents(__DIR__.'/../fixtures/shaheen-products.json'), true);

        Http::fake(function ($request) use ($productsJson) {
            if (str_contains($request->url(), 'shaheeneg.com/ar/wp-json/wc/store/v1/products')) {
                return Http::response($productsJson, 200, ['Content-Type' => 'application/json']);
            }

            if (str_contains($request->url(), 'shaheeneg.com/wp-content/')) {
                return Http::response('fake-image', 200, ['Content-Type' => 'image/jpeg']);
            }

            return Http::response('', 404);
        });

        $items = app(ShaheenScraperService::class)->scrapeCollections(['refrigerators'], 1);
        $importer = app(ScrapedProductImporter::class);
        $importer->import($items, true);

        $this->assertDatabaseHas('categories', ['slug' => 'home-appliances', 'name' => 'الأجهزة المنزلية']);
        $this->assertDatabaseHas('categories', ['slug' => 'refrigerators', 'name' => 'ثلاجات']);
        $this->assertDatabaseHas('products', ['sku' => 'SHAHEEN-RDNE430K12B']);
        $this->assertSame(1, $importer->getCreatedCount());
    }
}
