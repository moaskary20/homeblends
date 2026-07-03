<?php

namespace Tests\Feature;

use App\Services\ProductScraper\AriikaScraperService;
use App\Services\ProductScraper\ScrapedProductImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AriikaProductScrapeTest extends TestCase
{
    use RefreshDatabase;

    public function test_scrapes_products_from_shopify_json(): void
    {
        $fixture = file_get_contents(__DIR__.'/../fixtures/ariika-products.json');

        Http::fake([
            '*' => Http::response($fixture, 200),
        ]);

        $items = app(AriikaScraperService::class)->scrapeFurniture(['indoor-furniture'], 5);

        $this->assertCount(1, $items);
        $this->assertSame('ARIIKA-TEST-SKU-001', $items->first()['sku']);
        $this->assertSame('Test Sofa', $items->first()['name']);
        $this->assertSame('الأثاث الداخلي', $items->first()['category_name']);
        $this->assertSame(50000.0, $items->first()['regular_price']);
        $this->assertSame(60000.0, $items->first()['discount_price']);
        $this->assertCount(2, $items->first()['image_urls']);
    }

    public function test_continues_when_one_collection_fails(): void
    {
        $fixture = file_get_contents(__DIR__.'/../fixtures/ariika-products.json');

        Http::fake(function ($request) use ($fixture) {
            if (str_contains($request->url(), 'living-room-1/products.json')) {
                throw new \Illuminate\Http\Client\ConnectionException('DNS timeout');
            }

            if (str_contains($request->url(), 'products.json')) {
                return Http::response($fixture, 200);
            }

            return Http::response([], 404);
        });

        $scraper = app(AriikaScraperService::class);
        $items = $scraper->scrapeFurniture(['indoor-furniture', 'living-room-1'], 5);

        $this->assertCount(1, $items);
        $this->assertCount(1, $scraper->getScrapeErrors());
        $this->assertSame('living-room-1', $scraper->getScrapeErrors()->first()['handle']);
    }

    public function test_imports_scraped_products_with_categories(): void
    {
        Storage::fake('public');

        Http::fake(function ($request) {
            if (str_contains($request->url(), 'products.json')) {
                return Http::response(
                    file_get_contents(__DIR__.'/../fixtures/ariika-products.json'),
                    200
                );
            }

            return Http::response('fake-image', 200, ['Content-Type' => 'image/jpeg']);
        });

        $items = app(AriikaScraperService::class)->scrapeFurniture(['indoor-furniture'], 5);
        $importer = app(ScrapedProductImporter::class);
        $importer->import($items, true);

        $this->assertDatabaseHas('categories', ['slug' => 'athath', 'name' => 'أثاث']);
        $this->assertDatabaseHas('categories', ['slug' => 'ariika-indoor-furniture']);
        $this->assertDatabaseHas('products', ['sku' => 'ARIIKA-TEST-SKU-001', 'name' => 'Test Sofa']);
        $this->assertSame(1, $importer->getCreatedCount());
        $product = \App\Models\Product::where('sku', 'ARIIKA-TEST-SKU-001')->first();
        $this->assertSame(2, $product->images()->count());
        $this->assertNotNull($product->main_image);
    }

    public function test_import_restores_soft_deleted_subcategory(): void
    {
        Storage::fake('public');

        $parent = \App\Models\Category::create([
            'name' => 'أثاث',
            'slug' => 'athath',
            'is_active' => true,
        ]);

        $livingRoom = \App\Models\Category::create([
            'name' => 'ليفينج روم',
            'slug' => 'living-room',
            'parent_id' => $parent->id,
            'is_active' => true,
        ]);

        $trashed = \App\Models\Category::create([
            'name' => 'الأثاث الداخلي',
            'slug' => 'ariika-indoor-furniture',
            'parent_id' => $livingRoom->id,
            'is_active' => true,
        ]);
        $trashed->delete();

        Http::fake(function ($request) {
            if (str_contains($request->url(), 'products.json')) {
                return Http::response(
                    file_get_contents(__DIR__.'/../fixtures/ariika-products.json'),
                    200
                );
            }

            return Http::response('fake-image', 200, ['Content-Type' => 'image/jpeg']);
        });

        $items = app(AriikaScraperService::class)->scrapeFurniture(['indoor-furniture'], 5);
        app(ScrapedProductImporter::class)->import($items, true);

        $child = \App\Models\Category::where('slug', 'ariika-indoor-furniture')->first();
        $this->assertNotNull($child);
        $this->assertNull($child->deleted_at);
        $this->assertSame($livingRoom->id, $child->parent_id);
    }

    public function test_product_gallery_renders_all_images(): void
    {
        $category = \App\Models\Category::create(['name' => 'قسم', 'slug' => 'gal', 'is_active' => true]);
        $product = \App\Models\Product::create([
            'category_id' => $category->id,
            'name' => 'معرض',
            'slug' => 'gallery-product',
            'sku' => 'GAL-1',
            'regular_price' => 100,
            'stock_quantity' => 1,
            'status' => \App\Enums\ProductStatus::Published,
            'main_image' => 'products/scraped/gal-1.jpg',
        ]);
        \App\Models\ProductImage::create([
            'product_id' => $product->id,
            'path' => 'products/scraped/gal-1.jpg',
            'sort_order' => 0,
        ]);
        \App\Models\ProductImage::create([
            'product_id' => $product->id,
            'path' => 'products/scraped/gal-2.jpg',
            'sort_order' => 1,
        ]);

        $response = $this->get(route('shop.products.show', $product->slug));

        $response->assertOk();
        $response->assertSee('data-gallery-thumb', false);
    }
}
