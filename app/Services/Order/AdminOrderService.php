<?php

namespace App\Services\Order;

use App\Enums\AffiliateCommissionStatus;
use App\Enums\CouponType;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Repositories\Contracts\CouponRepositoryInterface;
use App\Services\Coupon\CouponService;
use App\Services\FlashSale\FlashSaleService;
use App\Services\Inventory\InventoryService;
use App\Services\Loyalty\LoyaltyService;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Shipping\ShippingService;
use App\Services\Tax\TaxService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminOrderService
{
    public function __construct(
        protected ShippingService $shippingService,
        protected TaxService $taxService,
        protected CouponService $couponService,
        protected CouponRepositoryInterface $couponRepository,
        protected LoyaltyService $loyaltyService,
        protected NotificationDispatcher $notifications,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function preview(array $data): array
    {
        $lines = $this->resolveLineItems($data['items'] ?? []);
        $subtotal = $lines->sum('total');
        $weight = $lines->sum('weight');
        $country = strtoupper($data['shipping_country'] ?? 'EG');
        $userId = $this->resolveUserId($data);

        $manualDiscount = max(0, (float) ($data['manual_discount'] ?? 0));
        $couponDiscount = 0;
        $coupon = null;

        if (! empty($data['coupon_code']) && $userId) {
            try {
                $coupon = $this->couponService->validate($data['coupon_code'], $userId, $subtotal);
                $couponDiscount = $this->couponService->calculateDiscount(
                    $data['coupon_code'],
                    $userId,
                    $subtotal
                );
            } catch (ValidationException) {
                $couponDiscount = 0;
                $coupon = null;
            }
        }

        $vipDiscount = 0;
        if ($userId) {
            $user = User::find($userId);
            if ($user) {
                $vipDiscount = $this->loyaltyService->calculateVipDiscount($user, $subtotal);
            }
        }

        $discountTotal = min($subtotal, $manualDiscount + $couponDiscount + $vipDiscount);
        $subtotalAfterDiscount = max(0, $subtotal - $discountTotal);

        $shippingAmount = 0.0;
        $shippingName = null;

        if (! empty($data['manual_free_shipping'])) {
            $shippingName = __('ecommerce.manual_free_shipping');
        } elseif (! empty($data['shipping_rate_id'])) {
            try {
                $shipping = $this->shippingService->calculate(
                    (int) $data['shipping_rate_id'],
                    $subtotal,
                    $weight,
                    $country
                );
                $shippingAmount = $shipping['amount'];
                $shippingName = $shipping['name'];
            } catch (\InvalidArgumentException) {
                // Rate not applicable yet.
            }
        }

        if ($coupon && $coupon->type === CouponType::FreeShipping) {
            $shippingAmount = 0;
        }

        $tax = $this->taxService->calculate($subtotalAfterDiscount, $country);
        $total = $subtotalAfterDiscount + $shippingAmount + $tax;

        return [
            'lines' => $lines,
            'subtotal' => $subtotal,
            'manual_discount' => $manualDiscount,
            'coupon_discount' => $couponDiscount,
            'vip_discount' => $vipDiscount,
            'discount_total' => $discountTotal,
            'shipping_amount' => $shippingAmount,
            'shipping_name' => $shippingName,
            'tax_amount' => $tax,
            'total' => $total,
            'weight' => $weight,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): Order
    {
        return DB::transaction(function () use ($data, $actor) {
            $lines = $this->resolveLineItems($data['items'] ?? []);

            if ($lines->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => [__('ecommerce.order_items_required')],
                ]);
            }

            $this->assertStockAvailable($lines, (bool) ($data['decrement_stock'] ?? true));

            $preview = $this->preview($data);
            $userId = $this->resolveUserId($data);
            $user = $userId ? User::find($userId) : null;

            $shippingAddress = $this->buildAddress($data, 'shipping', $user);
            $billingAddress = ! empty($data['billing_same_as_shipping'])
                ? $shippingAddress
                : $this->buildAddress($data, 'billing', $user);

            $country = strtoupper($data['shipping_country'] ?? 'EG');
            $coupon = null;

            if (! empty($data['coupon_code']) && $userId) {
                $coupon = $this->couponService->validate($data['coupon_code'], $userId, $preview['subtotal']);
            }

            $status = OrderStatus::tryFrom($data['status'] ?? '') ?? (
                ($data['payment_status'] ?? 'pending') === 'paid'
                    ? OrderStatus::Confirmed
                    : OrderStatus::Pending
            );

            $paymentStatus = $data['payment_status'] ?? 'pending';
            $paidAt = $paymentStatus === 'paid'
                ? ($data['paid_at'] ?? now())
                : null;

            $order = Order::create([
                'order_number' => 'HB-'.strtoupper(Str::random(10)),
                'user_id' => $userId,
                'status' => $status,
                'billing_address' => $billingAddress,
                'shipping_address' => $shippingAddress,
                'shipping_rate_id' => $data['shipping_rate_id'] ?? null,
                'shipping_method' => $preview['shipping_name'],
                'subtotal' => $preview['subtotal'],
                'discount_amount' => $preview['discount_total'],
                'shipping_amount' => $preview['shipping_amount'],
                'tax_amount' => $preview['tax_amount'],
                'total' => $preview['total'],
                'currency' => $user?->currency ?? 'EGP',
                'coupon_id' => $coupon?->id,
                'notes' => $data['notes'] ?? null,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'payment_status' => $paymentStatus,
                'paid_at' => $paidAt,
            ]);

            foreach ($lines as $line) {
                $order->items()->create([
                    'product_id' => $line['product_id'],
                    'product_variant_id' => $line['product_variant_id'],
                    'product_name' => $line['product_name'],
                    'sku' => $line['sku'],
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'total' => $line['total'],
                    'variant_snapshot' => $line['variant_snapshot'],
                ]);
            }

            $order->statusHistory()->create([
                'status' => $status->value,
                'comment' => __('ecommerce.admin_order_created'),
                'user_id' => $actor->id,
            ]);

            if ($coupon && $userId) {
                $this->couponService->recordUsage($coupon, $userId, $order->id);
            }

            if ($user && ($data['decrement_stock'] ?? true)) {
                foreach ($lines as $line) {
                    $this->decrementStock($line);

                    if ($line['flash_sale_product'] ?? null) {
                        app(FlashSaleService::class)->recordSale($line['flash_sale_product'], $line['quantity']);
                    }
                }
            }

            if ($user && $preview['total'] > 0) {
                $pointsEarned = $this->loyaltyService->calculateEarnedPoints($preview['total']);
                $order->update(['loyalty_points_earned' => $pointsEarned]);
                $this->loyaltyService->awardPoints($user, $pointsEarned, $order);
            }

            $order = $order->fresh(['items', 'user']);

            if ($data['send_notification'] ?? true) {
                $this->notifications->orderPlaced($order);
            }

            return $order;
        });
    }

    public function removeItem(Order $order, OrderItem $item, ?User $actor = null): Order
    {
        if ((int) $item->order_id !== (int) $order->id) {
            throw ValidationException::withMessages([
                'items' => [__('ecommerce.order_item_not_in_order')],
            ]);
        }

        if (in_array($order->status, [OrderStatus::Cancelled, OrderStatus::Refunded], true)) {
            throw ValidationException::withMessages([
                'items' => [__('ecommerce.cannot_edit_order_items')],
            ]);
        }

        return DB::transaction(function () use ($order, $item, $actor) {
            $order->load(['items']);

            if ($order->items->count() <= 1) {
                throw ValidationException::withMessages([
                    'items' => [__('ecommerce.cannot_remove_last_order_item')],
                ]);
            }

            $originalSubtotal = (float) $order->subtotal;
            $originalDiscount = (float) $order->discount_amount;

            $this->restoreItemStock($item);
            $this->reverseFlashSaleForItem($item);

            $productName = $item->product_name;
            $item->delete();

            $order = $this->recalculateTotals(
                $order->fresh(['items.product', 'coupon', 'user', 'payments', 'affiliateCommission']),
                $originalSubtotal,
                $originalDiscount
            );

            $order->statusHistory()->create([
                'status' => $order->status->value,
                'comment' => __('ecommerce.order_item_removed_history', [
                    'product' => $productName,
                ]),
                'user_id' => $actor?->id,
            ]);

            return $order->fresh(['items', 'user']);
        });
    }

    public function recalculateTotals(Order $order, ?float $originalSubtotal = null, ?float $originalDiscount = null): Order
    {
        $order->loadMissing(['items.product', 'coupon', 'user', 'payments', 'affiliateCommission']);

        $subtotal = round((float) $order->items->sum('total'), 2);
        $weight = $order->items->sum(
            fn (OrderItem $item) => (float) ($item->product?->weight ?? 0) * (int) $item->quantity
        );
        $country = strtoupper($order->shipping_address['country'] ?? 'EG');

        $coupon = $order->coupon;
        $couponDiscount = 0;
        $freeShippingFromCoupon = false;

        if ($coupon) {
            if ($this->couponService->stillAppliesToSubtotal($coupon, $subtotal)) {
                $couponDiscount = $this->couponService->discountAmount($coupon, $subtotal);
                $freeShippingFromCoupon = $coupon->type === CouponType::FreeShipping;
            } else {
                $this->couponService->releaseUsageForOrder($coupon, $order->id);
                $order->coupon_id = null;
            }
        }

        $vipDiscount = 0;
        if ($order->user) {
            $vipDiscount = $this->loyaltyService->calculateVipDiscount($order->user, $subtotal);
        }

        $loyaltyDiscount = $order->loyalty_points_redeemed
            ? $this->loyaltyService->redeemValue((int) $order->loyalty_points_redeemed)
            : 0;

        $computedDiscount = $couponDiscount + $vipDiscount + $loyaltyDiscount;
        $hadCoupon = $coupon !== null || $order->coupon_id !== null;
        $manualDiscount = 0.0;

        if ($computedDiscount <= 0 && ! $hadCoupon && $originalDiscount && $originalSubtotal && $originalSubtotal > 0) {
            $manualDiscount = round($originalDiscount * ($subtotal / $originalSubtotal), 2);
        }

        $discountTotal = min($subtotal, $computedDiscount + $manualDiscount);

        $subtotalAfterDiscount = max(0, $subtotal - $discountTotal);

        $shippingAmount = (float) $order->shipping_amount;
        $shippingName = $order->shipping_method;

        if ($freeShippingFromCoupon || (! $order->shipping_rate_id && (float) $order->shipping_amount === 0.0)) {
            $shippingAmount = 0;
        } elseif ($order->shipping_rate_id) {
            try {
                $shipping = $this->shippingService->calculate(
                    (int) $order->shipping_rate_id,
                    $subtotal,
                    $weight,
                    $country
                );
                $shippingAmount = $shipping['amount'];
                $shippingName = $shipping['name'];
            } catch (\InvalidArgumentException) {
                if ($this->shippingService->qualifiesForFreeShipping($subtotal)) {
                    $shippingAmount = 0;
                }
            }
        }

        $tax = $this->taxService->calculate($subtotalAfterDiscount, $country);
        $paymentFee = $this->paymentFee($order);
        $total = round($subtotalAfterDiscount + $shippingAmount + $tax + $paymentFee, 2);

        $loyaltyPointsEarned = $order->user
            ? $this->loyaltyService->calculateEarnedPoints($total)
            : (int) $order->loyalty_points_earned;

        $this->syncLoyaltyPointsEarned($order, $loyaltyPointsEarned);

        $order->update([
            'subtotal' => $subtotal,
            'discount_amount' => $discountTotal,
            'shipping_amount' => $shippingAmount,
            'shipping_method' => $shippingName,
            'tax_amount' => $tax,
            'total' => $total,
            'coupon_id' => $order->coupon_id,
            'loyalty_points_earned' => $loyaltyPointsEarned,
        ]);

        $order->payments()
            ->where('status', 'pending')
            ->update(['amount' => $total]);

        $this->syncPendingAffiliateCommission($order->fresh(['affiliateCommission']));

        return $order->fresh(['items', 'user', 'coupon']);
    }

    protected function restoreItemStock(OrderItem $item): void
    {
        $product = $item->product_id
            ? Product::withTrashed()->find($item->product_id)
            : null;

        if (! $product) {
            return;
        }

        $variant = $item->product_variant_id
            ? ProductVariant::query()->find($item->product_variant_id)
            : null;

        app(InventoryService::class)->increment($product, (int) $item->quantity, $variant);
    }

    protected function reverseFlashSaleForItem(OrderItem $item): void
    {
        $product = $item->product;

        if (! $product) {
            return;
        }

        $entry = app(FlashSaleService::class)->findActiveEntry($product, $item->variant);

        if ($entry) {
            app(FlashSaleService::class)->reverseSale($entry, (int) $item->quantity);
        }
    }

    protected function paymentFee(Order $order): float
    {
        $payment = $order->payments->first();

        if (! $payment || ! is_array($payment->payload)) {
            return 0;
        }

        return (float) ($payment->payload['payment_fee'] ?? 0);
    }

    protected function syncLoyaltyPointsEarned(Order $order, int $newPoints): void
    {
        $user = $order->user;
        $previous = (int) $order->loyalty_points_earned;
        $diff = $newPoints - $previous;

        if (! $user || $diff === 0) {
            return;
        }

        try {
            $this->loyaltyService->adjustPoints(
                $user,
                $diff,
                __('ecommerce.order_totals_recalculated'),
            );
        } catch (\InvalidArgumentException) {
            // Customer may have already spent the originally awarded points.
        }
    }

    protected function syncPendingAffiliateCommission(Order $order): void
    {
        $commission = $order->affiliateCommission;

        if (! $commission || $commission->status !== AffiliateCommissionStatus::Pending) {
            return;
        }

        $orderAmount = max(0, round((float) $order->subtotal - (float) $order->discount_amount, 2));
        $amount = round($orderAmount * ((float) $commission->commission_rate / 100), 2);

        $commission->update([
            'order_amount' => $orderAmount,
            'commission_amount' => $amount,
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    protected function resolveLineItems(array $items): Collection
    {
        return collect($items)
            ->filter(fn ($item) => ! empty($item['product_id']) && (int) ($item['quantity'] ?? 0) > 0)
            ->map(function (array $item) {
                $product = Product::query()->findOrFail($item['product_id']);
                $variant = ! empty($item['product_variant_id'])
                    ? ProductVariant::query()
                        ->where('product_id', $product->id)
                        ->findOrFail($item['product_variant_id'])
                    : null;

                $quantity = (int) $item['quantity'];
                $pricing = app(FlashSaleService::class)->resolveUnitPrice($product, $variant);
                $unitPrice = isset($item['unit_price']) && $item['unit_price'] !== ''
                    ? (float) $item['unit_price']
                    : $pricing['price'];

                return [
                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,
                    'product_name' => $product->name,
                    'sku' => $variant?->sku ?? $product->sku,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total' => round($unitPrice * $quantity, 2),
                    'weight' => (float) ($product->weight ?? 0) * $quantity,
                    'variant_snapshot' => $variant?->toArray(),
                    'variant' => $variant,
                    'product' => $product,
                    'flash_sale_product' => $pricing['flash_sale_product'] ?? null,
                ];
            })
            ->values();
    }

    protected function assertStockAvailable(Collection $lines, bool $willDecrement): void
    {
        if (! $willDecrement) {
            return;
        }

        $inventory = app(InventoryService::class);

        foreach ($lines as $line) {
            try {
                $inventory->assertAvailable($line['product'], (int) $line['quantity'], $line['variant']);
            } catch (ValidationException $e) {
                throw ValidationException::withMessages([
                    'items' => [__('ecommerce.insufficient_stock', ['product' => $line['product_name']])],
                ]);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $line
     */
    protected function decrementStock(array $line): void
    {
        app(InventoryService::class)->decrement(
            $line['product'],
            (int) $line['quantity'],
            $line['variant']
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function resolveUserId(array $data): ?int
    {
        if (($data['customer_type'] ?? 'registered') === 'guest') {
            return null;
        }

        return isset($data['user_id']) ? (int) $data['user_id'] : null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function buildAddress(array $data, string $prefix, ?User $user): array
    {
        if ($prefix === 'shipping' && ($data['customer_type'] ?? '') === 'guest') {
            return [
                'name' => $data['guest_name'] ?? '',
                'phone' => $data['guest_phone'] ?? '',
                'email' => $data['guest_email'] ?? null,
                'city' => $data['shipping_city'] ?? '',
                'address' => $data['shipping_address_line'] ?? '',
                'postal_code' => $data['shipping_postal_code'] ?? null,
                'country' => strtoupper($data['shipping_country'] ?? 'EG'),
            ];
        }

        $key = $prefix === 'shipping' ? 'shipping' : 'billing';

        return [
            'name' => $data["{$key}_name"] ?? $user?->name ?? '',
            'phone' => $data["{$key}_phone"] ?? $user?->phone ?? '',
            'email' => $data["{$key}_email"] ?? $user?->email ?? null,
            'city' => $data["{$key}_city"] ?? '',
            'address' => $data["{$key}_address_line"] ?? '',
            'postal_code' => $data["{$key}_postal_code"] ?? null,
            'country' => strtoupper($data["{$key}_country"] ?? $data['shipping_country'] ?? 'EG'),
        ];
    }
}
