<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopProductQuantityTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_page_uses_variant_stock_for_quantity_max(): void
    {
        $category = Category::create(['name' => 'Cat', 'slug' => 'cat', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Variant item',
            'slug' => 'variant-item',
            'sku' => 'SKU-VAR',
            'regular_price' => 100,
            'stock_quantity' => 1,
            'status' => ProductStatus::Published,
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'VAR-L',
            'price' => 100,
            'stock_quantity' => 8,
            'is_default' => true,
        ]);

        $response = $this->get(route('shop.products.show', $product->slug));

        $response->assertOk()
            ->assertSee('data-qty-plus', false)
            ->assertSee('data-stock="8"', false)
            ->assertSee('max="8"', false);
    }
}
