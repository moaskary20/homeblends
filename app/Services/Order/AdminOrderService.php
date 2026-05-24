<?php

namespace App\Services\Order;

use App\Enums\CouponType;
use App\Enums\OrderStatus;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Repositories\Contracts\CouponRepositoryInterface;
use App\Services\Coupon\CouponService;
use App\Services\Loyalty\LoyaltyService;
use App\Services\FlashSale\FlashSaleService;
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

        foreach ($lines as $line) {
            $available = $line['variant']
                ? $line['variant']->stock_quantity
                : $line['product']->stock_quantity;

            if ($line['quantity'] > $available) {
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
        if ($line['variant']) {
            $line['variant']->decrement('stock_quantity', $line['quantity']);
        } else {
            $line['product']->decrement('stock_quantity', $line['quantity']);
        }
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
