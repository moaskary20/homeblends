<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coupon\ApplyCouponRequest;
use App\Services\Cart\CartService;
use App\Services\Coupon\CouponService;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function __construct(
        protected CartService $cartService,
        protected CouponService $couponService,
    ) {}

    public function apply(ApplyCouponRequest $request)
    {
        $cart = $this->cartService->resolveCart(
            $request->user()?->id,
            $request->header('X-Session-Id')
        );

        $totals = $this->cartService->getTotals($cart);
        $discount = $this->couponService->calculateDiscount(
            $request->code,
            $request->user()?->id ?? 0,
            $totals['subtotal']
        );

        $this->cartService->applyCoupon($cart, $request->code);

        return response()->json([
            'message' => __('ecommerce.coupon_applied'),
            'discount' => $discount,
            'totals' => $this->cartService->getTotals($cart->fresh(['items.product'])),
        ]);
    }
}
