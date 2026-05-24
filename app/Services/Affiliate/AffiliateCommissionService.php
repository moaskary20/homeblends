<?php

namespace App\Services\Affiliate;

use App\Enums\AffiliateCommissionStatus;
use App\Enums\OrderStatus;
use App\Models\Affiliate;
use App\Models\AffiliateClick;
use App\Models\AffiliateCommission;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class AffiliateCommissionService
{
    public function recordForOrder(Order $order): ?AffiliateCommission
    {
        if (! $order->affiliate_id) {
            return null;
        }

        $existing = AffiliateCommission::query()->where('order_id', $order->id)->first();

        if ($existing) {
            return $existing;
        }

        $affiliate = Affiliate::query()->active()->find($order->affiliate_id);

        if (! $affiliate) {
            return null;
        }

        if ($order->user_id && $affiliate->user_id === $order->user_id) {
            return null;
        }

        $orderAmount = $this->commissionableAmount($order);
        $rate = $affiliate->effectiveCommissionRate();
        $amount = round($orderAmount * ($rate / 100), 2);

        if ($amount <= 0) {
            return null;
        }

        return DB::transaction(function () use ($order, $affiliate, $orderAmount, $rate, $amount) {
            $commission = AffiliateCommission::create([
                'affiliate_id' => $affiliate->id,
                'order_id' => $order->id,
                'affiliate_click_id' => $order->affiliate_click_id,
                'order_amount' => $orderAmount,
                'commission_rate' => $rate,
                'commission_amount' => $amount,
                'currency' => $order->currency,
                'status' => AffiliateCommissionStatus::Pending,
            ]);

            $affiliate->increment('total_orders');

            if ($order->affiliate_click_id) {
                AffiliateClick::query()
                    ->whereKey($order->affiliate_click_id)
                    ->where('converted', false)
                    ->update([
                        'converted' => true,
                        'converted_at' => now(),
                    ]);
            }

            return $commission;
        });
    }

    public function handleOrderStatusChange(Order $order): void
    {
        $commission = AffiliateCommission::query()->where('order_id', $order->id)->first();

        if (! $commission && $order->affiliate_id) {
            $commission = $this->recordForOrder($order);
        }

        if (! $commission) {
            return;
        }

        if (in_array($order->status, [OrderStatus::Cancelled, OrderStatus::Refunded], true)) {
            $this->cancel($commission);

            return;
        }

        $shouldApprove = match (config('affiliate.commission_on')) {
            'paid' => $order->payment_status === 'paid',
            default => $order->status === OrderStatus::Delivered,
        };

        if ($shouldApprove && $commission->status === AffiliateCommissionStatus::Pending) {
            $this->approve($commission);
        }
    }

    public function approve(AffiliateCommission $commission): AffiliateCommission
    {
        if ($commission->status !== AffiliateCommissionStatus::Pending) {
            return $commission;
        }

        return DB::transaction(function () use ($commission) {
            $commission->update([
                'status' => AffiliateCommissionStatus::Approved,
                'approved_at' => now(),
            ]);

            $affiliate = $commission->affiliate;
            $affiliate->increment('balance', $commission->commission_amount);
            $affiliate->increment('total_earned', $commission->commission_amount);

            return $commission->fresh();
        });
    }

    public function cancel(AffiliateCommission $commission): AffiliateCommission
    {
        return DB::transaction(function () use ($commission) {
            if ($commission->status === AffiliateCommissionStatus::Approved) {
                $commission->affiliate->decrement('balance', $commission->commission_amount);
                $commission->affiliate->decrement('total_earned', $commission->commission_amount);
            }

            $commission->update([
                'status' => AffiliateCommissionStatus::Cancelled,
                'cancelled_at' => now(),
            ]);

            return $commission->fresh();
        });
    }

    public function markPaid(AffiliateCommission $commission): void
    {
        if ($commission->status === AffiliateCommissionStatus::Approved) {
            $commission->update(['status' => AffiliateCommissionStatus::Paid]);
        }
    }

    protected function commissionableAmount(Order $order): float
    {
        return max(0, round((float) $order->subtotal - (float) $order->discount_amount, 2));
    }
}
