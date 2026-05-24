<?php

namespace App\Services\Coupon;

use App\Enums\CouponType;
use App\Models\Coupon;
use App\Repositories\Contracts\CouponRepositoryInterface;
use Illuminate\Validation\ValidationException;

class CouponService
{
    public function __construct(
        protected CouponRepositoryInterface $couponRepository,
    ) {}

    public function calculateDiscount(string $code, int $userId, float $subtotal): float
    {
        $coupon = $this->validate($code, $userId, $subtotal);

        return match ($coupon->type) {
            CouponType::Fixed => min((float) $coupon->value, $subtotal),
            CouponType::Percentage => round($subtotal * ($coupon->value / 100), 2),
            CouponType::FreeShipping => 0,
        };
    }

    public function validate(string $code, int $userId, float $subtotal): Coupon
    {
        $coupon = $this->couponRepository->findByCode($code);

        if (! $coupon || ! $coupon->isValid()) {
            throw ValidationException::withMessages(['coupon' => [__('Invalid or expired coupon.')]]);
        }

        if ($coupon->min_cart_amount && $subtotal < $coupon->min_cart_amount) {
            throw ValidationException::withMessages([
                'coupon' => [__('Minimum cart amount not met.')],
            ]);
        }

        if ($coupon->usage_per_user && $userId > 0) {
            $userUsages = $coupon->usages()->where('user_id', $userId)->count();
            if ($userUsages >= $coupon->usage_per_user) {
                throw ValidationException::withMessages(['coupon' => [__('Coupon usage limit reached.')]]);
            }
        }

        return $coupon;
    }

    public function recordUsage(Coupon $coupon, int $userId, int $orderId): void
    {
        $coupon->usages()->create(['user_id' => $userId, 'order_id' => $orderId]);
        $coupon->increment('used_count');
    }
}
