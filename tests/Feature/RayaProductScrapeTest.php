<?php

namespace Tests\Feature;

use App\Services\ProductScraper\RayaScraperService;
use App\Services\ProductScraper\ScrapedProductImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RayaProductScrapeTest extends TestCase
{
    use RefreshDatabase;

    public function test_scrapes_products_from_raya_graphql_category(): void
    {
        $categoryJson = json_decode(file_get_contents(__DIR__.'/../fixtures/raya-category-refrigerators.json'), true);
        $productJson = json_decode(file_get_contents(__DIR__.'/../fixtures/raya-product-detail.json'), true);

        Http::fake(function ($request) use ($categoryJson, $productJson) {
            if (! str_contains($request->url(), 'graphql')) {
                return Http::response('', 404);
            }

            $body = $request->data();
            $query = (string) ($body['query'] ?? '');

            if (str_contains($query, 'CategoryProducts')) {
                return Http::response($categoryJson, 200, ['Content-Type' => 'application/json']);
            }

            if (str_contains($query, 'ProductDetail')) {
                return Http::response($productJson, 200, ['Content-Type' => 'application/json']);
            }

            if (str_contains($query, 'Ping')) {
                return Http::response([
                    'data' => ['categoryList' => [['products' => ['total_count' => 2]]]],
                ], 200, ['Content-Type' => 'application/json']);
            }

            return Http::response('', 404);
        });

        $items = app(RayaScraperService::class)->scrapeCollections(['refrigerators'], 5);

        $this->assertGreaterThanOrEqual(1, $items->count());
        $this->assertSame('RAYA-KSV36VL30U', $items->first()['sku']);
        $this->assertStringContainsString('بوش', $items->first()['name']);
        $this->assertSame('ثلاجات', $items->first()['category_name']);
        $this->assertSame('refrigerators', $items->first()['category_slug']);
        $this->assertSame('home-appliances', $items->first()['parent_category_slug']);
        $this->assertSame('الأجهزة المنزلية', $items->first()['parent_category_name']);
        $this->assertSame(54999.0, $items->first()['regular_price']);
        $this->assertSame(49999.0, $items->first()['discount_price']);
        $this->assertGreaterThanOrEqual(2, count($items->first()['image_urls']));
    }

    public function test_imports_scraped_raya_products_with_categories(): void
    {
        Storage::fake('public');
        $categoryJson = json_decode(file_get_contents(__DIR__.'/../fixtures/raya-category-refrigerators.json'), true);
        $productJson = json_decode(file_get_contents(__DIR__.'/../fixtures/raya-product-detail.json'), true);

        Http::fake(function ($request) use ($categoryJson, $productJson) {
            if (! str_contains($request->url(), 'graphql')) {
                if (str_contains($request->url(), 'fastly.net/media/')) {
                    return Http::response('fake-image', 200, ['Content-Type' => 'image/png']);
                }

                return Http::response('', 404);
            }

            $body = $request->data();
            $query = (string) ($body['query'] ?? '');

            if (str_contains($query, 'CategoryProducts')) {
                return Http::response($categoryJson, 200, ['Content-Type' => 'application/json']);
            }

            if (str_contains($query, 'ProductDetail')) {
                return Http::response($productJson, 200, ['Content-Type' => 'application/json']);
            }

            return Http::response('', 404);
        });

        $items = app(RayaScraperService::class)->scrapeCollections(['refrigerators'], 1);
        $importer = app(ScrapedProductImporter::class);
        $importer->import($items, true);

        $this->assertDatabaseHas('categories', ['slug' => 'home-appliances', 'name' => 'الأجهزة المنزلية']);
        $this->assertDatabaseHas('categories', ['slug' => 'refrigerators', 'name' => 'ثلاجات']);
        $this->assertDatabaseHas('products', ['sku' => 'RAYA-KSV36VL30U']);
        $this->assertSame(1, $importer->getCreatedCount());
    }
}
