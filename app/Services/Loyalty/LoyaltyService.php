<?php

namespace App\Services\Loyalty;

use App\Models\LoyaltyTransaction;
use App\Models\Order;
use App\Models\User;
use App\Models\VipLevel;
use Illuminate\Support\Facades\DB;

class LoyaltyService
{
    public function calculateEarnedPoints(float $orderTotal): int
    {
        $base = (int) floor($orderTotal / config('ecommerce.loyalty.earn_per_currency', 10));
        $multiplier = (float) config('ecommerce.loyalty.earn_multiplier', 1);

        return (int) floor($base * $multiplier);
    }

    public function redeemValue(int $points): float
    {
        return round($points * $this->pointValue(), 2);
    }

    public function pointValue(): float
    {
        return (float) config('ecommerce.loyalty.point_value', 0.1);
    }

    public function getVipDiscountPercent(User $user): float
    {
        $user->loadMissing('vipLevel');

        return (float) ($user->vipLevel?->discount_percent ?? 0);
    }

    public function calculateVipDiscount(User $user, float $subtotal): float
    {
        $percent = $this->getVipDiscountPercent($user);

        if ($percent <= 0) {
            return 0;
        }

        return round($subtotal * ($percent / 100), 2);
    }

    public function maxRedeemablePoints(User $user, float $eligibleSubtotal): int
    {
        $balance = (int) $user->loyalty_points;
        if ($balance <= 0 || $eligibleSubtotal <= 0) {
            return 0;
        }

        $maxPercent = (int) config('ecommerce.loyalty.max_redeem_percent', 50);
        $maxDiscount = $eligibleSubtotal * ($maxPercent / 100);
        $maxBySubtotal = (int) floor($maxDiscount / $this->pointValue());

        return max(0, min($balance, $maxBySubtotal));
    }

    public function validateRedemption(User $user, int $points, float $eligibleSubtotal): void
    {
        if ($points <= 0) {
            return;
        }

        $minRedeem = (int) config('ecommerce.loyalty.min_redeem_points', 10);
        if ($points < $minRedeem) {
            throw new \InvalidArgumentException(__('ecommerce.loyalty_min_redeem', ['min' => $minRedeem]));
        }

        if ($user->loyalty_points < $points) {
            throw new \InvalidArgumentException(__('ecommerce.loyalty_insufficient'));
        }

        $max = $this->maxRedeemablePoints($user, $eligibleSubtotal);
        if ($points > $max) {
            throw new \InvalidArgumentException(__('ecommerce.loyalty_max_redeem', ['max' => $max]));
        }
    }

    public function awardPoints(User $user, int $points, Order $order): void
    {
        if ($points <= 0) {
            return;
        }

        $user->increment('loyalty_points', $points);

        LoyaltyTransaction::create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'points' => $points,
            'type' => 'earn',
            'description' => "نقاط من الطلب {$order->order_number}",
            'expires_at' => now()->addMonths((int) config('ecommerce.loyalty.expiry_months', 12)),
        ]);

        $this->syncVipLevel($user->fresh());
    }

    public function deductPoints(User $user, int $points, Order $order): void
    {
        if ($points <= 0) {
            return;
        }

        if ($user->loyalty_points < $points) {
            throw new \InvalidArgumentException(__('ecommerce.loyalty_insufficient'));
        }

        $user->decrement('loyalty_points', $points);

        LoyaltyTransaction::create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'points' => -$points,
            'type' => 'redeem',
            'description' => "استبدال على الطلب {$order->order_number}",
        ]);
    }

    public function validateWalletRedemption(User $user, int $points): void
    {
        if ($points <= 0) {
            throw new \InvalidArgumentException(__('ecommerce.loyalty_redeem_invalid'));
        }

        $minRedeem = (int) config('ecommerce.loyalty.min_redeem_points', 10);
        if ($points < $minRedeem) {
            throw new \InvalidArgumentException(__('ecommerce.loyalty_min_redeem', ['min' => $minRedeem]));
        }

        if ($user->loyalty_points < $points) {
            throw new \InvalidArgumentException(__('ecommerce.loyalty_insufficient'));
        }
    }

    public function maxWalletRedeemPoints(User $user): int
    {
        return max(0, (int) $user->loyalty_points);
    }

    public function redeemToWallet(User $user, int $points): float
    {
        return DB::transaction(function () use ($user, $points) {
            $this->validateWalletRedemption($user, $points);

            $amount = $this->redeemValue($points);

            $user->decrement('loyalty_points', $points);
            $user->increment('store_credit', $amount);

            LoyaltyTransaction::create([
                'user_id' => $user->id,
                'points' => -$points,
                'type' => 'wallet',
                'description' => __('ecommerce.wallet_redeem_description', [
                    'amount' => number_format($amount, 2),
                ]),
            ]);

            $this->syncVipLevel($user->fresh());

            return $amount;
        });
    }

    public function adjustPoints(User $user, int $points, string $description, ?User $admin = null): void
    {
        DB::transaction(function () use ($user, $points, $description, $admin): void {
            if ($points > 0) {
                $user->increment('loyalty_points', $points);
            } elseif ($points < 0) {
                if ($user->loyalty_points < abs($points)) {
                    throw new \InvalidArgumentException(__('ecommerce.loyalty_insufficient'));
                }
                $user->decrement('loyalty_points', abs($points));
            } else {
                return;
            }

            LoyaltyTransaction::create([
                'user_id' => $user->id,
                'points' => $points,
                'type' => 'adjust',
                'description' => $description.($admin ? " (بواسطة {$admin->name})" : ''),
                'expires_at' => $points > 0
                    ? now()->addMonths((int) config('ecommerce.loyalty.expiry_months', 12))
                    : null,
            ]);

            $this->syncVipLevel($user->fresh());
        });
    }

    public function expirePoints(): int
    {
        $expiredCount = 0;

        $candidates = LoyaltyTransaction::query()
            ->where('type', 'earn')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->whereNull('expired_at')
            ->with('user')
            ->orderBy('expires_at')
            ->get();

        foreach ($candidates as $earn) {
            $user = $earn->user;
            if (! $user) {
                $earn->update(['expired_at' => now()]);
                continue;
            }

            $toExpire = min($earn->points, (int) $user->loyalty_points);
            if ($toExpire > 0) {
                $user->decrement('loyalty_points', $toExpire);

                LoyaltyTransaction::create([
                    'user_id' => $user->id,
                    'points' => -$toExpire,
                    'type' => 'expire',
                    'description' => 'انتهاء صلاحية النقاط',
                ]);

                $this->syncVipLevel($user->fresh());
                $expiredCount += $toExpire;
            }

            $earn->update(['expired_at' => now()]);
        }

        return $expiredCount;
    }

    public function syncVipLevel(User $user): void
    {
        $level = VipLevel::query()
            ->where('min_points', '<=', $user->loyalty_points)
            ->orderByDesc('min_points')
            ->first();

        if ($level && $user->vip_level_id !== $level->id) {
            $user->update(['vip_level_id' => $level->id]);
        }
    }

    public function getProgramInfo(User $user): array
    {
        $user->loadMissing('vipLevel');

        return [
            'points' => (int) $user->loyalty_points,
            'store_credit' => (float) $user->store_credit,
            'max_wallet_redeem_points' => $this->maxWalletRedeemPoints($user),
            'vip_level' => $user->vipLevel,
            'point_value' => $this->pointValue(),
            'earn_per_currency' => (int) config('ecommerce.loyalty.earn_per_currency', 10),
            'earn_rate_label' => __('ecommerce.loyalty_earn_rate', [
                'amount' => config('ecommerce.loyalty.earn_per_currency', 10),
            ]),
            'expiry_months' => (int) config('ecommerce.loyalty.expiry_months', 12),
            'min_redeem_points' => (int) config('ecommerce.loyalty.min_redeem_points', 10),
            'max_redeem_percent' => (int) config('ecommerce.loyalty.max_redeem_percent', 50),
            'vip_discount_percent' => $this->getVipDiscountPercent($user),
        ];
    }
}
