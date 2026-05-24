<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\Cart\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_add_item_to_guest_cart(): void
    {
        $category = Category::create(['name' => 'Cat', 'slug' => 'cat', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Item',
            'slug' => 'item',
            'sku' => 'SKU-1',
            'regular_price' => 50,
            'stock_quantity' => 5,
            'status' => ProductStatus::Published,
        ]);

        $response = $this->withHeader('X-Session-Id', 'guest-session-123')
            ->postJson('/api/v1/cart/items', [
                'product_id' => $product->id,
                'quantity' => 2,
            ]);

        $response->assertOk()
            ->assertJsonPath('totals.items_count', 2);
    }

    public function test_logged_in_user_sees_items_on_cart_page_after_api_add(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => 'Cat', 'slug' => 'cat', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'منتج موحّد',
            'slug' => 'unified-product',
            'sku' => 'SKU-2',
            'regular_price' => 100,
            'stock_quantity' => 10,
            'status' => ProductStatus::Published,
        ]);

        $this->actingAs($user)
            ->withHeader('X-Session-Id', 'logged-in-session')
            ->postJson('/api/v1/cart/items', [
                'product_id' => $product->id,
                'quantity' => 1,
            ])
            ->assertOk();

        $this->actingAs($user)
            ->get(route('shop.cart'))
            ->assertOk()
            ->assertSee('منتج موحّد', false);
    }

    public function test_resolve_cart_merges_guest_items_for_logged_in_user(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => 'Cat', 'slug' => 'cat', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'قبل الدمج',
            'slug' => 'before-merge',
            'sku' => 'SKU-3',
            'regular_price' => 80,
            'stock_quantity' => 5,
            'status' => ProductStatus::Published,
        ]);

        $sessionId = 'shared-browser-session';
        $cartService = app(CartService::class);

        $guestCart = $cartService->resolveCart(null, $sessionId);
        $cartService->addItem($guestCart, $product, 2);

        $userCart = $cartService->resolveCart($user->id, $sessionId);

        $this->assertSame(2, $userCart->items()->sum('quantity'));
        $this->assertDatabaseMissing('carts', [
            'session_id' => $sessionId,
            'user_id' => null,
        ]);
    }
}
