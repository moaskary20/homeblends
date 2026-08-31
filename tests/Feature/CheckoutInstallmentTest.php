<?php

namespace Tests\Feature;

use App\Enums\PaymentGateway as PaymentGatewayDriver;
use App\Enums\ProductStatus;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\InstallmentContract;
use App\Models\Offer;
use App\Models\OfferProduct;
use App\Models\PaymentGateway;
use App\Models\Product;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\TaxRate;
use App\Models\User;
use App\Services\Payment\PaymentGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutInstallmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_installment_checkout_creates_contract_and_schedule(): void
    {
        [$user, $rate, $offerProduct] = $this->seedCheckout($offerPrice = 1200, $months = 6);

        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $offerProduct->product_id,
            'offer_product_id' => $offerProduct->id,
            'quantity' => 1,
            'unit_price' => $offerPrice,
        ]);

        $response = $this->actingAs($user)->postJson(route('shop.checkout.store'), $this->payload($rate->id, PaymentGatewayDriver::LocalProvider, true));

        $response->assertCreated();
        $this->assertDatabaseCount('installment_contracts', 1);
        $this->assertDatabaseCount('installment_payments', 6);

        $contract = InstallmentContract::first();
        $this->assertSame(1200.0, (float) $contract->total_amount);
        $this->assertSame(200.0, (float) $contract->installments->first()->amount);

        $firstCharge = $contract->order->payments->first();
        $this->assertGreaterThan(200, (float) $firstCharge->amount);
        $this->assertSame($offerProduct->id, $contract->order->items->first()->offer_product_id);
    }

    public function test_checkout_uses_the_installment_plan_selected_on_the_cart(): void
    {
        [$user, $rate, $offerProduct] = $this->seedCheckout($offerPrice = 1200, $months = 6);

        $offerProduct->offer->update([
            'installment_plans' => [6, 12],
        ]);

        $cart = Cart::create([
            'user_id' => $user->id,
            'installment_months' => 12,
        ]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $offerProduct->product_id,
            'offer_product_id' => $offerProduct->id,
            'quantity' => 1,
            'unit_price' => $offerPrice,
        ]);

        $this->actingAs($user)
            ->postJson(route('shop.checkout.store'), $this->payload($rate->id, PaymentGatewayDriver::LocalProvider, true))
            ->assertCreated();

        $this->assertDatabaseCount('installment_payments', 12);
        $contract = InstallmentContract::first();
        $this->assertSame(12, (int) $contract->months);
        $this->assertSame(100.0, (float) $contract->installments->first()->amount);
    }

    public function test_checkout_page_shows_the_selected_installment_schedule(): void
    {
        [$user, , $offerProduct] = $this->seedCheckout($offerPrice = 1200, $months = 6);

        $offerProduct->offer->update([
            'installment_plans' => [6, 12],
        ]);

        $cart = Cart::create([
            'user_id' => $user->id,
            'installment_months' => 12,
        ]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $offerProduct->product_id,
            'offer_product_id' => $offerProduct->id,
            'quantity' => 1,
            'unit_price' => $offerPrice,
        ]);

        $this->actingAs($user)
            ->get(route('shop.checkout'))
            ->assertOk()
            ->assertSee('نظام التقسيط المختار')
            ->assertSee('تقسيط 12 شهر')
            ->assertSee('جدول الأقساط')
            ->assertSee('الشهر 1')
            ->assertSee('الشهر 12')
            ->assertSee('100.00')
            ->assertDontSee('تقسيط 6 شهر', false);
    }

    public function test_installment_rejected_for_regular_product(): void
    {
        [$user, $rate] = $this->seedCheckout(withoutOffer: true);
        $product = Product::factory()->create([
            'regular_price' => 100,
            'stock_quantity' => 5,
            'status' => ProductStatus::Published,
        ]);
        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 100,
        ]);

        $this->actingAs($user)
            ->postJson(route('shop.checkout.store'), $this->payload($rate->id, PaymentGatewayDriver::LocalProvider, true))
            ->assertStatus(422);
    }

    public function test_installment_checkout_allows_cash_on_delivery(): void
    {
        [$user, $rate, $offerProduct] = $this->seedCheckout($offerPrice = 1200, $months = 6);
        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $offerProduct->product_id,
            'offer_product_id' => $offerProduct->id,
            'quantity' => 1,
            'unit_price' => 1200,
        ]);

        $this->actingAs($user)
            ->postJson(route('shop.checkout.store'), $this->payload($rate->id, PaymentGatewayDriver::CashOnDelivery, true))
            ->assertCreated();

        $this->assertDatabaseCount('installment_contracts', 1);
        $this->assertDatabaseCount('installment_payments', 6);

        $order = \App\Models\Order::query()->first();
        $this->assertSame(PaymentGatewayDriver::CashOnDelivery->value, $order->payment_method);
        $this->assertSame('pending', $order->payment_status);

        $payment = $order->payments->first();
        $this->assertSame(PaymentGatewayDriver::CashOnDelivery->value, $payment->gateway);
        $this->assertSame('pending', $payment->status);
        $this->assertSame(250.0, (float) $payment->amount);
    }

    public function test_offer_cart_without_installment_flag_does_not_create_contract(): void
    {
        [$user, $rate, $offerProduct] = $this->seedCheckout();
        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $offerProduct->product_id,
            'offer_product_id' => $offerProduct->id,
            'quantity' => 1,
            'unit_price' => 1200,
        ]);

        $this->actingAs($user)
            ->postJson(route('shop.checkout.store'), $this->payload($rate->id, PaymentGatewayDriver::CashOnDelivery, false))
            ->assertCreated();

        $this->assertDatabaseCount('installment_contracts', 0);
    }

    public function test_adding_offer_locks_offer_price_for_every_product(): void
    {
        [, , $offerProduct] = $this->seedCheckout(899);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('shop.offers.cart', $offerProduct->offer->slug));

        $response->assertOk();
        $this->assertEquals(899.0, (float) CartItem::query()->where('offer_product_id', $offerProduct->id)->value('unit_price'));
    }

    /**
     * @return array{0: User, 1: ShippingRate, 2?: OfferProduct}
     */
    protected function seedCheckout(float $offerPrice = 1200, int $months = 6, bool $withoutOffer = false): array
    {
        TaxRate::create(['country' => 'EG', 'name' => 'VAT', 'rate' => 0, 'is_active' => true]);
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
        PaymentGateway::create([
            'code' => PaymentGatewayDriver::LocalProvider->value,
            'name' => 'محلي',
            'is_active' => true,
            'sort_order' => 2,
            'config' => [],
        ]);
        app(PaymentGatewayService::class)->clearCache();

        $user = User::factory()->create();

        if ($withoutOffer) {
            return [$user, $rate];
        }

        $product = Product::factory()->create([
            'regular_price' => 2000,
            'stock_quantity' => 10,
            'status' => ProductStatus::Published,
        ]);
        $offer = Offer::create([
            'name' => 'عرض تقسيط',
            'slug' => 'installment-offer',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addMonth(),
            'is_active' => true,
            'installment_months' => $months,
        ]);
        $offerProduct = OfferProduct::create([
            'offer_id' => $offer->id,
            'product_id' => $product->id,
            'offer_price' => $offerPrice,
        ]);

        return [$user, $rate, $offerProduct];
    }

    protected function payload(int $rateId, PaymentGatewayDriver $gateway, bool $installments): array
    {
        return [
            'shipping_address' => [
                'first_name' => 'أحمد',
                'last_name' => 'محمد',
                'email' => 'ahmed-inst@example.com',
                'phone' => '01012345678',
                'address_line_1' => '12 شارع النيل',
                'city' => 'القاهرة',
                'country' => 'EG',
            ],
            'shipping_rate_id' => $rateId,
            'payment_gateway' => $gateway->value,
            'loyalty_points' => 0,
            'pay_in_installments' => $installments,
        ];
    }
}
