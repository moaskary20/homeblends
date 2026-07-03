<?php

namespace App\Http\Controllers\Api;

use App\Http\Concerns\ResolvesCartSession;
use App\Http\Controllers\Controller;
use App\Http\Resources\LoyaltyTransactionResource;
use App\Models\LoyaltyTransaction;
use App\Services\Cart\CartService;
use App\Services\Coupon\CouponService;
use App\Services\Loyalty\LoyaltyService;
use Illuminate\Http\Request;

class LoyaltyController extends Controller
{
    public function __construct(
        protected LoyaltyService $loyaltyService,
        protected CartService $cartService,
        protected CouponService $couponService,
    ) {}

    public function balance(Request $request)
    {
        return response()->json($this->loyaltyService->getProgramInfo($request->user()));
    }

    public function history(Request $request)
    {
        $transactions = LoyaltyTransaction::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return LoyaltyTransactionResource::collection($transactions);
    }

    public function preview(Request $request)
    {
        $request->validate([
            'points' => ['required', 'integer', 'min:0'],
            'coupon_code' => ['nullable', 'string'],
        ]);

        $user = $request->user();
        $cart = $this->cartService->resolveCart($user->id, $this->resolveCartSessionId($request));
        $totals = $this->cartService->getTotals($cart);

        $couponDiscount = $request->coupon_code
            ? $this->couponService->calculateDiscount($request->coupon_code, $user->id, $totals['subtotal'])
            : 0;

        $vipDiscount = $this->loyaltyService->calculateVipDiscount($user, $totals['subtotal']);
        $eligible = max(0, $totals['subtotal'] - $couponDiscount - $vipDiscount);
        $points = $request->integer('points');
        $maxPoints = $this->loyaltyService->maxRedeemablePoints($user, $eligible);

        $valid = $points === 0;
        $message = null;

        if ($points > 0) {
            try {
                $this->loyaltyService->validateRedemption($user, $points, $eligible);
                $valid = true;
            } catch (\InvalidArgumentException $e) {
                $message = $e->getMessage();
            }
        }

        return response()->json([
            'points' => $points,
            'max_redeemable_points' => $maxPoints,
            'discount_value' => $this->loyaltyService->redeemValue($points),
            'eligible_subtotal' => $eligible,
            'vip_discount' => $vipDiscount,
            'valid' => $valid,
            'message' => $message,
        ]);
    }

    public function redeemToWallet(Request $request)
    {
        $minRedeem = (int) config('ecommerce.loyalty.min_redeem_points', 10);

        $request->validate([
            'points' => ['required', 'integer', 'min:'.$minRedeem],
        ]);

        try {
            $amount = $this->loyaltyService->redeemToWallet(
                $request->user(),
                $request->integer('points'),
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => __('ecommerce.points_redeemed_to_wallet', [
                'amount' => number_format($amount, 2),
            ]),
            'amount' => $amount,
            'program' => $this->loyaltyService->getProgramInfo($request->user()->fresh()),
        ]);
    }
}
