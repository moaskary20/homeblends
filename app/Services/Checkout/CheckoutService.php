<?php

namespace App\Services\Checkout;

use App\Enums\OrderStatus;
use App\Enums\PaymentGateway as PaymentGatewayDriver;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Repositories\Contracts\CouponRepositoryInterface;
use App\Services\Cart\CartService;
use App\Services\Coupon\CouponService;
use App\Services\Loyalty\LoyaltyService;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Payment\PaymentGatewayService;
use App\Services\Payment\PaymentService;
use App\Services\Affiliate\AffiliateCommissionService;
use App\Services\Affiliate\AffiliateTrackingService;
use App\Services\Bundle\BundleService;
use App\Services\FlashSale\FlashSaleService;
use App\Services\Shipping\ShippingService;
use App\Services\Tax\TaxService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutService
{
    public function __construct(
        protected CartService $cartService,
        protected CouponService $couponService,
        protected ShippingService $shippingService,
        protected TaxService $taxService,
        protected LoyaltyService $loyaltyService,
        protected PaymentService $paymentService,
        protected PaymentGatewayService $paymentGateways,
        protected CouponRepositoryInterface $couponRepository,
        protected NotificationDispatcher $notifications,
    ) {}

    public function placeOrder(
        Cart $cart,
        User $user,
        array $shippingAddress,
        array $billingAddress,
        int $shippingRateId,
        ?string $couponCode,
        PaymentGatewayDriver $gateway,
        ?int $loyaltyPointsToRedeem = 0,
        ?string $notes = null,
    ): Order {
        return DB::transaction(function () use (
            $cart, $user, $shippingAddress, $billingAddress,
            $shippingRateId, $couponCode, $gateway, $loyaltyPointsToRedeem, $notes
        ) {
            $cart->load(['items.product', 'items.variant']);
            $totals = $this->cartService->getTotals($cart);

            $cartWeight = $cart->items->sum(function ($item) {
                if ($item->product_bundle_id && is_array($item->bundle_snapshot)) {
                    return collect($item->bundle_snapshot['items'] ?? [])->sum(function ($row) use ($item) {
                        $product = Product::find($row['product_id'] ?? 0);

                        return (float) ($product->weight ?? 0) * ($row['quantity'] ?? 1) * $item->quantity;
                    });
                }

                return (float) ($item->product->weight ?? 0) * $item->quantity;
            });
            $shipping = $this->shippingService->calculate(
                $shippingRateId,
                $totals['subtotal'],
                $cartWeight,
                $shippingAddress['country'] ?? 'EG'
            );
            $discount = $couponCode
                ? $this->couponService->calculateDiscount($couponCode, $user->id, $totals['subtotal'])
                : 0;

            $vipDiscount = $this->loyaltyService->calculateVipDiscount($user, $totals['subtotal']);
            $eligibleForPoints = max(0, $totals['subtotal'] - $discount - $vipDiscount);

            if ($loyaltyPointsToRedeem > 0) {
                $this->loyaltyService->validateRedemption($user, $loyaltyPointsToRedeem, $eligibleForPoints);
            }

            $loyaltyDiscount = $loyaltyPointsToRedeem > 0
                ? $this->loyaltyService->redeemValue($loyaltyPointsToRedeem)
                : 0;

            $subtotalAfterDiscount = max(0, $eligibleForPoints - $loyaltyDiscount);
            $tax = $this->taxService->calculate($subtotalAfterDiscount, $shippingAddress['country'] ?? 'EG');
            $totalBeforeFee = $subtotalAfterDiscount + $shipping['amount'] + $tax;

            $gatewayConfig = $this->paymentGateways->assertAvailableForOrder($gateway->value, $totalBeforeFee);
            $paymentFee = $gatewayConfig->codFee();
            $total = $totalBeforeFee + $paymentFee;

            $coupon = $couponCode ? $this->couponRepository->findByCode($couponCode) : null;
            $affiliateAttribution = app(AffiliateTrackingService::class)->resolveForCheckout($user->id);

            $order = Order::create([
                'order_number' => 'HB-'.strtoupper(Str::random(10)),
                'user_id' => $user->id,
                'affiliate_id' => $affiliateAttribution['affiliate_id'] ?? null,
                'affiliate_click_id' => $affiliateAttribution['affiliate_click_id'] ?? null,
                'status' => OrderStatus::Pending,
                'billing_address' => $billingAddress,
                'shipping_address' => $shippingAddress,
                'shipping_rate_id' => $shippingRateId,
                'shipping_method' => $shipping['name'],
                'subtotal' => $totals['subtotal'],
                'discount_amount' => $discount + $vipDiscount + $loyaltyDiscount,
                'shipping_amount' => $shipping['amount'],
                'tax_amount' => $tax,
                'total' => $total,
                'currency' => $user->currency ?? 'EGP',
                'coupon_id' => $coupon?->id,
                'loyalty_points_redeemed' => $loyaltyPointsToRedeem,
                'notes' => $notes,
                'payment_method' => $gateway->value,
                'payment_status' => 'pending',
            ]);

            $bundleService = app(BundleService::class);

            foreach ($cart->items as $item) {
                if ($item->product_bundle_id) {
                    $bundleService->createOrderItemsFromCartLine($order, $item);

                    continue;
                }

                $order->items()->create([
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'product_name' => $item->product->name,
                    'sku' => $item->variant?->sku ?? $item->product->sku,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total' => $item->subtotal,
                    'variant_snapshot' => $item->variant?->toArray(),
                ]);

                $this->decrementStock($item);

                $flashEntry = app(FlashSaleService::class)->findActiveEntry(
                    $item->product,
                    $item->variant
                );
                if ($flashEntry) {
                    app(FlashSaleService::class)->recordSale($flashEntry, $item->quantity);
                }
            }

            $order->statusHistory()->create([
                'status' => OrderStatus::Pending->value,
                'comment' => 'Order placed',
                'user_id' => $user->id,
            ]);

            if ($coupon) {
                $this->couponService->recordUsage($coupon, $user->id, $order->id);
            }

            if ($loyaltyPointsToRedeem > 0) {
                $this->loyaltyService->deductPoints($user, $loyaltyPointsToRedeem, $order);
            }

            $pointsEarned = $this->loyaltyService->calculateEarnedPoints($total);
            $order->update(['loyalty_points_earned' => $pointsEarned]);
            $this->loyaltyService->awardPoints($user, $pointsEarned, $order);

            app(AffiliateCommissionService::class)->recordForOrder($order);

            $this->paymentService->initiate($order, $gateway, [
                'gateway_name' => $gatewayConfig->displayName(),
                'payment_fee' => $paymentFee,
                'instructions' => $gatewayConfig->instructions,
            ]);

            $cart->items()->delete();

            $order = $order->load(['items', 'payments', 'user']);
            $this->notifications->orderPlaced($order);

            return $order;
        });
    }

    protected function decrementStock($item): void
    {
        if ($item->variant) {
            $item->variant->decrement('stock_quantity', $item->quantity);
        } else {
            $item->product->decrement('stock_quantity', $item->quantity);
        }
    }
}
