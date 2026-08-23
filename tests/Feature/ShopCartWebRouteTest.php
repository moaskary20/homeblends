<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopCartWebRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_cart_items_route_adds_product_with_session_and_csrf(): void
    {
        $category = Category::create(['name' => 'Cat', 'slug' => 'cat', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Web item',
            'slug' => 'web-item',
            'sku' => 'SKU-WEB',
            'regular_price' => 50,
            'stock_quantity' => 5,
            'status' => ProductStatus::Published,
        ]);

        $response = $this->withSession([])
            ->post('/cart/items', [
                'product_id' => $product->id,
                'quantity' => 2,
            ], [
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ]);

        $response->assertOk()
            ->assertJsonPath('totals.items_count', 2);
    }
}
