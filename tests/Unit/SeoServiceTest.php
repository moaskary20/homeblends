<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Product;
use App\Services\Seo\SeoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_meta_includes_schema_and_og(): void
    {
        $product = Product::factory()->create([
            'name' => 'Blender Pro',
            'meta_title' => 'خلاط برو',
            'meta_description' => 'أفضل خلاط في مصر',
            'short_description' => 'خلاط قوي',
            'regular_price' => 1200,
            'stock_quantity' => 5,
        ]);

        $seo = app(SeoService::class)->forProduct($product);

        $this->assertStringContainsString('خلاط برو', $seo->title);
        $this->assertSame('أفضل خلاط في مصر', $seo->description);
        $this->assertSame('product', $seo->ogType);
        $this->assertNotEmpty($seo->schema);

        $types = collect($seo->schema)->pluck('@type');
        $this->assertTrue($types->contains('Product'));
        $this->assertTrue($types->contains('BreadcrumbList'));
    }

    public function test_sitemap_includes_published_products(): void
    {
        $product = Product::factory()->create(['name' => 'Item A']);
        Category::factory()->create(['name' => 'Kitchen', 'is_active' => true]);

        $entries = app(SeoService::class)->sitemapEntries();
        $locs = collect($entries)->pluck('loc');

        $this->assertTrue($locs->contains(route('shop.products.show', $product->slug)));
        $this->assertTrue($locs->contains(route('shop.home')));
    }

    public function test_private_page_uses_noindex(): void
    {
        $seo = app(SeoService::class)->forPrivatePage('السلة');

        $this->assertSame('noindex, nofollow', $seo->robots);
    }
}
