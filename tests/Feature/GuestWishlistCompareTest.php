<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\CompareList;
use App\Models\Product;
use App\Models\Wishlist;
use App\Services\Shop\CompareListService;
use App\Services\Shop\WishlistService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestWishlistCompareTest extends TestCase
{
    use RefreshDatabase;

    private function product(): Product
    {
        $category = Category::create(['name' => 'Cat', 'slug' => 'cat', 'is_active' => true]);

        return Product::create([
            'category_id' => $category->id,
            'name' => 'منتج ضيف',
            'slug' => 'guest-product',
            'sku' => 'SKU-GUEST',
            'regular_price' => 100,
            'stock_quantity' => 5,
            'status' => ProductStatus::Published,
        ]);
    }

    public function test_guests_with_different_sessions_do_not_share_wishlist(): void
    {
        $product = $this->product();
        $wishlist = app(WishlistService::class);

        $wishlist->toggle(null, 'guest-session-a', $product);

        $this->assertSame(1, $wishlist->count(null, 'guest-session-a'));
        $this->assertSame(0, $wishlist->count(null, 'guest-session-b'));
    }

    public function test_guests_with_different_sessions_do_not_share_compare(): void
    {
        $product = $this->product();
        $compare = app(CompareListService::class);

        $compare->toggle(null, 'guest-session-a', $product);

        $this->assertSame(1, $compare->count(null, 'guest-session-a'));
        $this->assertSame(0, $compare->count(null, 'guest-session-b'));
    }

    public function test_orphan_null_session_wishlist_rows_are_not_visible_to_guests(): void
    {
        $product = $this->product();

        Wishlist::create([
            'user_id' => null,
            'session_id' => null,
            'product_id' => $product->id,
        ]);

        $this->assertSame(0, app(WishlistService::class)->count(null, 'brand-new-session'));
    }

    public function test_orphan_null_session_compare_rows_are_not_visible_to_guests(): void
    {
        $product = $this->product();

        CompareList::create([
            'user_id' => null,
            'session_id' => null,
            'product_id' => $product->id,
        ]);

        $products = app(CompareListService::class)->products(null, 'brand-new-session');

        $this->assertTrue($products->isEmpty());
    }
}
