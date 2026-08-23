<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
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

    public function test_web_cart_items_route_removes_item_with_session_and_csrf(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => 'Cat', 'slug' => 'cat', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Web item',
            'slug' => 'web-item-remove',
            'sku' => 'SKU-WEB-RM',
            'regular_price' => 50,
            'stock_quantity' => 5,
            'status' => ProductStatus::Published,
        ]);

        $this->actingAs($user);

        $addResponse = $this->post('/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
        ], [
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $addResponse->assertOk();

        $cartItemId = $addResponse->json('item.id');

        $this->delete("/cart/items/{$cartItemId}", [], [
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ])
            ->assertOk()
            ->assertJsonPath('totals.items_count', 0);
    }

    public function test_web_cart_items_route_updates_quantity_with_session_and_csrf(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => 'Cat', 'slug' => 'cat', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Web item',
            'slug' => 'web-item-qty',
            'sku' => 'SKU-WEB-QTY',
            'regular_price' => 50,
            'stock_quantity' => 5,
            'status' => ProductStatus::Published,
        ]);

        $this->actingAs($user);

        $addResponse = $this->post('/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
        ], [
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $addResponse->assertOk();

        $cartItemId = $addResponse->json('item.id');

        $this->patch("/cart/items/{$cartItemId}", [
            'quantity' => 3,
        ], [
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ])
            ->assertOk()
            ->assertJsonPath('totals.items_count', 3);
    }
}
