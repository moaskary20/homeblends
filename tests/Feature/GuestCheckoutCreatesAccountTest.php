<?php

namespace Tests\Feature;

use App\Enums\PaymentGateway as PaymentGatewayDriver;
use App\Enums\ProductStatus;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\PaymentGateway;
use App\Models\Product;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\TaxRate;
use App\Models\User;
use App\Services\Payment\PaymentGatewayService;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class GuestCheckoutCreatesAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_checkout_creates_account_and_places_order(): void
    {
        Notification::fake();
        [$rate] = $this->seedCheckoutCatalog();

        $session = $this->app['session']->getId() ?: $this->startSession()->getId();
        $cart = Cart::create(['session_id' => $session, 'user_id' => null]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => Product::query()->first()->id,
            'quantity' => 1,
            'unit_price' => 100,
        ]);

        $payload = $this->checkoutPayload($rate->id, 'guest-checkout@example.com', 'SecretPass123!');

        $response = $this->withSession([])->postJson(route('shop.checkout.store'), $payload);

        $response->assertCreated();
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'guest-checkout@example.com',
            'name' => 'أحمد محمد',
            'phone' => '01012345678',
        ]);
        $this->assertDatabaseHas('orders', [
            'user_id' => User::query()->where('email', 'guest-checkout@example.com')->value('id'),
        ]);
        $this->assertSame(1, Order::query()->count());

        Notification::assertSentTo(
            User::query()->where('email', 'guest-checkout@example.com')->first(),
            VerifyEmail::class,
        );
    }

    public function test_guest_checkout_requires_password(): void
    {
        [$rate] = $this->seedCheckoutCatalog();

        $response = $this->postJson(route('shop.checkout.store'), $this->checkoutPayload($rate->id, 'needs-password@example.com'));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'needs-password@example.com']);
    }

    public function test_guest_checkout_rejects_existing_email(): void
    {
        [$rate] = $this->seedCheckoutCatalog();
        User::factory()->create(['email' => 'exists@example.com']);

        $response = $this->postJson(
            route('shop.checkout.store'),
            $this->checkoutPayload($rate->id, 'exists@example.com', 'SecretPass123!')
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['shipping_address.email']);
        $this->assertGuest();
    }

    /**
     * @return array{0: ShippingRate}
     */
    protected function seedCheckoutCatalog(): array
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

        $category = Category::create(['name' => 'Cat', 'slug' => 'cat-guest-checkout', 'is_active' => true]);
        Product::create([
            'category_id' => $category->id,
            'name' => 'Guest checkout item',
            'slug' => 'guest-checkout-item',
            'sku' => 'SKU-GUEST-CHK',
            'regular_price' => 100,
            'stock_quantity' => 10,
            'status' => ProductStatus::Published,
        ]);

        return [$rate];
    }

    protected function checkoutPayload(int $shippingRateId, string $email, ?string $password = null): array
    {
        $payload = [
            'shipping_address' => [
                'first_name' => 'أحمد',
                'last_name' => 'محمد',
                'email' => $email,
                'phone' => '01012345678',
                'alternate_phone' => null,
                'address_line_1' => '12 شارع النيل',
                'address_line_2' => null,
                'city' => 'المعادي',
                'state' => 'القاهرة',
                'postal_code' => '11742',
                'country' => 'EG',
            ],
            'billing_address' => [
                'first_name' => 'أحمد',
                'last_name' => 'محمد',
                'email' => $email,
                'phone' => '01012345678',
                'alternate_phone' => null,
                'address_line_1' => '12 شارع النيل',
                'address_line_2' => null,
                'city' => 'المعادي',
                'state' => 'القاهرة',
                'postal_code' => '11742',
                'country' => 'EG',
            ],
            'shipping_rate_id' => $shippingRateId,
            'payment_gateway' => PaymentGatewayDriver::CashOnDelivery->value,
            'loyalty_points' => 0,
        ];

        if ($password !== null) {
            $payload['password'] = $password;
        }

        return $payload;
    }
}
