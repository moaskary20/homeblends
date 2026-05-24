<?php

namespace Tests\Unit;

use App\Enums\AffiliateCommissionStatus;
use App\Enums\AffiliateStatus;
use App\Enums\OrderStatus;
use App\Models\Affiliate;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Affiliate\AffiliateCommissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AffiliateCommissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_records_commission_on_order(): void
    {
        $affiliateUser = User::factory()->create();
        $customer = User::factory()->create();

        $affiliate = Affiliate::create([
            'user_id' => $affiliateUser->id,
            'code' => 'PARTNER1',
            'display_name' => 'Partner',
            'status' => AffiliateStatus::Active,
            'commission_rate' => 10,
        ]);

        $order = Order::create([
            'order_number' => 'HB-TEST001',
            'user_id' => $customer->id,
            'affiliate_id' => $affiliate->id,
            'status' => OrderStatus::Pending,
            'billing_address' => ['city' => 'Cairo'],
            'shipping_address' => ['city' => 'Cairo'],
            'subtotal' => 1000,
            'discount_amount' => 0,
            'shipping_amount' => 50,
            'tax_amount' => 0,
            'total' => 1050,
            'currency' => 'EGP',
            'payment_status' => 'pending',
        ]);

        $commission = app(AffiliateCommissionService::class)->recordForOrder($order);

        $this->assertNotNull($commission);
        $this->assertSame(100.0, (float) $commission->commission_amount);
        $this->assertSame(AffiliateCommissionStatus::Pending, $commission->status);
    }

    public function test_approves_commission_when_order_delivered(): void
    {
        $affiliateUser = User::factory()->create();
        $customer = User::factory()->create();

        $affiliate = Affiliate::create([
            'user_id' => $affiliateUser->id,
            'code' => 'PARTNER2',
            'display_name' => 'Partner 2',
            'status' => AffiliateStatus::Active,
            'commission_rate' => 10,
        ]);

        $order = Order::create([
            'order_number' => 'HB-TEST002',
            'user_id' => $customer->id,
            'affiliate_id' => $affiliate->id,
            'status' => OrderStatus::Shipped,
            'billing_address' => [],
            'shipping_address' => [],
            'subtotal' => 500,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'tax_amount' => 0,
            'total' => 500,
            'currency' => 'EGP',
            'payment_status' => 'paid',
        ]);

        app(AffiliateCommissionService::class)->recordForOrder($order);
        $order->update(['status' => OrderStatus::Delivered]);

        app(AffiliateCommissionService::class)->handleOrderStatusChange($order->fresh());

        $affiliate->refresh();
        $this->assertSame(50.0, (float) $affiliate->balance);
    }

    public function test_blocks_self_referral(): void
    {
        $user = User::factory()->create();
        $affiliate = Affiliate::create([
            'user_id' => $user->id,
            'code' => 'SELF',
            'display_name' => 'Self',
            'status' => AffiliateStatus::Active,
        ]);

        $order = Order::create([
            'order_number' => 'HB-SELF',
            'user_id' => $user->id,
            'affiliate_id' => $affiliate->id,
            'status' => OrderStatus::Pending,
            'billing_address' => [],
            'shipping_address' => [],
            'subtotal' => 200,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'tax_amount' => 0,
            'total' => 200,
            'currency' => 'EGP',
            'payment_status' => 'pending',
        ]);

        $commission = app(AffiliateCommissionService::class)->recordForOrder($order);

        $this->assertNull($commission);
    }
}
