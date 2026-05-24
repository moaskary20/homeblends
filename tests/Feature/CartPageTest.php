<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\Cart\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_page_renders_with_items(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => 'قسم', 'slug' => 'cat', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'منتج السلة',
            'slug' => 'cart-product',
            'sku' => 'CART-1',
            'regular_price' => 250,
            'stock_quantity' => 5,
            'status' => ProductStatus::Published,
        ]);

        $cartService = app(CartService::class);
        $cart = $cartService->resolveCart($user->id, null);
        $cartService->addItem($cart, $product, 2);

        $response = $this->actingAs($user)->get(route('shop.cart'));

        $response->assertOk();
        $response->assertSee('منتج السلة', false);
        $response->assertSee('500', false);
        $response->assertSee('data-cart-count', false);
    }
}
