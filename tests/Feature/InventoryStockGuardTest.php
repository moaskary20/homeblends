<?php

namespace Tests\Feature;

use App\Enums\PaymentGateway as PaymentGatewayDriver;
use App\Enums\ProductStatus;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\PaymentGateway;
use App\Models\Product;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\TaxRate;
use App\Models\User;
use App\Services\Inventory\InventoryService;
use App\Services\Payment\PaymentGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InventoryStockGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_decrement_does_not_go_below_zero_for_unsigned_stock(): void
    {
        $category = Category::create(['name' => 'Cat', 'slug' => 'cat', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Zero stock',
            'slug' => 'zero-stock',
            'sku' => 'SKU-ZERO',
            'regular_price' => 50,
            'stock_quantity' => 0,
            'status' => ProductStatus::Published,
        ]);

        try {
            app(InventoryService::class)->decrement($product, 1);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException) {
            $this->assertSame(0, $product->fresh()->stock_quantity);
        }
    }

    public function test_checkout_rejects_out_of_stock_items_instead_of_sql_error(): void
    {
        TaxRate::create(['country' => 'EG', 'name' => 'VAT', 'rate' => 14, 'is_active' => true]);
        $zone = ShippingZone::create(['name' => 'مصر', 'countries' => ['EG'], 'is_active' => true]);
        $rate = ShippingRate::create([
            'shipping_zone_id' => $zone->id,
            'name' => 'توصيل',
            'type' => 'flat',
            'rate' => 50,
            'estimated_days' => 3,
            'is_active' => true,
        ]);
        PaymentGateway::create([
            'code' => PaymentGatewayDriver::CashOnDelivery->value,
            'name' => 'COD',
            'is_active' => true,
            'sort_order' => 1,
            'config' => [],
        ]);
        app(PaymentGatewayService::class)->clearCache();

        $user = User::factory()->create();
        $category = Category::create(['name' => 'Cat2', 'slug' => 'cat-2', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'نفد المخزون',
            'slug' => 'out-item',
            'sku' => 'SKU-OUT',
            'regular_price' => 100,
            'stock_quantity' => 0,
            'status' => ProductStatus::Published,
        ]);

        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 100,
        ]);

        $response = $this->actingAs($user)->postJson(route('shop.checkout.store'), [
            'shipping_address' => [
                'first_name' => 'أحمد',
                'last_name' => 'محمد',
                'email' => $user->email,
                'phone' => '01012345678',
                'address_line_1' => 'شارع 1',
                'city' => 'القاهرة',
                'country' => 'EG',
            ],
            'shipping_rate_id' => $rate->id,
            'payment_gateway' => PaymentGatewayDriver::CashOnDelivery->value,
            'loyalty_points' => 0,
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'المخزون غير كافٍ للمنتج: نفد المخزون']);

        $this->assertSame(0, $product->fresh()->stock_quantity);
    }
}
