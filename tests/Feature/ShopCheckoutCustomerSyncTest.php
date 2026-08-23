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
use App\Services\Payment\PaymentGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopCheckoutCustomerSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_syncs_customer_phone_and_default_address(): void
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

        $user = User::factory()->create([
            'phone' => null,
            'alternate_phone' => null,
            'email' => 'old@example.com',
        ]);
        $category = Category::create(['name' => 'Cat', 'slug' => 'cat', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Checkout item',
            'slug' => 'checkout-item',
            'sku' => 'SKU-CHK',
            'regular_price' => 100,
            'stock_quantity' => 10,
            'status' => ProductStatus::Published,
        ]);

        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 100,
        ]);

        $payload = [
            'shipping_address' => [
                'first_name' => 'أحمد',
                'last_name' => 'محمد',
                'email' => 'ahmed@example.com',
                'phone' => '01012345678',
                'alternate_phone' => '01198765432',
                'address_line_1' => '12 شارع النيل',
                'address_line_2' => 'الدور الثالث، شقة 5',
                'city' => 'المعادي',
                'state' => 'القاهرة',
                'postal_code' => '11742',
                'country' => 'EG',
            ],
            'billing_address' => [
                'first_name' => 'أحمد',
                'last_name' => 'محمد',
                'email' => 'ahmed@example.com',
                'phone' => '01012345678',
                'alternate_phone' => '01198765432',
                'address_line_1' => '12 شارع النيل',
                'address_line_2' => 'الدور الثالث، شقة 5',
                'city' => 'المعادي',
                'state' => 'القاهرة',
                'postal_code' => '11742',
                'country' => 'EG',
            ],
            'shipping_rate_id' => $rate->id,
            'payment_gateway' => PaymentGatewayDriver::CashOnDelivery->value,
            'loyalty_points' => 0,
        ];

        $response = $this->actingAs($user)->postJson(route('shop.checkout.store'), $payload);

        $response->assertCreated();

        $user->refresh();
        $this->assertSame('ahmed@example.com', $user->email);
        $this->assertSame('01012345678', $user->phone);
        $this->assertSame('01198765432', $user->alternate_phone);

        $this->assertDatabaseHas('addresses', [
            'user_id' => $user->id,
            'first_name' => 'أحمد',
            'last_name' => 'محمد',
            'phone' => '01012345678',
            'alternate_phone' => '01198765432',
            'address_line_1' => '12 شارع النيل',
            'address_line_2' => 'الدور الثالث، شقة 5',
            'city' => 'المعادي',
            'state' => 'القاهرة',
            'postal_code' => '11742',
            'country' => 'EG',
            'is_default' => true,
        ]);
    }
}
