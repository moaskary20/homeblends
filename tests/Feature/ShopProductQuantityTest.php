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

    public function test_product_page_allows_quantity_up_to_cart_limit_regardless_of_stock(): void
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
            'stock_quantity' => 1,
            'is_default' => true,
        ]);

        $response = $this->get(route('shop.products.show', $product->slug));

        $response->assertOk()
            ->assertSee('data-qty-plus', false)
            ->assertSee('max="99"', false)
            ->assertDontSee('max="1"', false);
    }
}
