<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\VipLevel;
use App\Services\Loyalty\LoyaltyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoyaltyServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculates_earned_points_from_order_total(): void
    {
        config(['ecommerce.loyalty.earn_per_currency' => 10]);

        $service = app(LoyaltyService::class);

        $this->assertSame(5, $service->calculateEarnedPoints(50));
    }

    public function test_redeem_value_uses_point_value_config(): void
    {
        config(['ecommerce.loyalty.point_value' => 0.1]);

        $service = app(LoyaltyService::class);

        $this->assertSame(10.0, $service->redeemValue(100));
    }

    public function test_vip_discount_percent_from_level(): void
    {
        $level = VipLevel::create([
            'name' => 'ذهبي',
            'slug' => 'gold-test',
            'min_points' => 0,
            'discount_percent' => 5,
        ]);

        $user = User::factory()->create(['vip_level_id' => $level->id]);

        $service = app(LoyaltyService::class);

        $this->assertSame(5.0, $service->getVipDiscountPercent($user));
        $this->assertSame(5.0, $service->calculateVipDiscount($user, 100));
    }

    public function test_max_redeemable_respects_balance_and_percent_cap(): void
    {
        config([
            'ecommerce.loyalty.point_value' => 0.1,
            'ecommerce.loyalty.max_redeem_percent' => 50,
        ]);

        $user = User::factory()->create(['loyalty_points' => 1000]);
        $service = app(LoyaltyService::class);

        $this->assertSame(500, $service->maxRedeemablePoints($user, 100));
    }

    public function test_redeem_to_wallet_moves_points_to_store_credit(): void
    {
        config([
            'ecommerce.loyalty.point_value' => 0.1,
            'ecommerce.loyalty.min_redeem_points' => 10,
        ]);

        $user = User::factory()->create(['loyalty_points' => 100, 'store_credit' => 0]);
        $service = app(LoyaltyService::class);

        $amount = $service->redeemToWallet($user, 50);

        $user->refresh();
        $this->assertSame(5.0, $amount);
        $this->assertSame(50, $user->loyalty_points);
        $this->assertSame(5.0, (float) $user->store_credit);
        $this->assertDatabaseHas('loyalty_transactions', [
            'user_id' => $user->id,
            'type' => 'wallet',
            'points' => -50,
        ]);
    }

    public function test_adjust_points_creates_transaction(): void
    {
        $user = User::factory()->create(['loyalty_points' => 0]);
        $service = app(LoyaltyService::class);

        $service->adjustPoints($user, 50, 'مكافأة ترحيبية');

        $user->refresh();
        $this->assertSame(50, $user->loyalty_points);
        $this->assertDatabaseHas('loyalty_transactions', [
            'user_id' => $user->id,
            'type' => 'adjust',
            'points' => 50,
        ]);
    }
}
