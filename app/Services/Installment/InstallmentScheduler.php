<?php

namespace App\Services\Installment;

use App\Enums\InstallmentContractStatus;
use App\Enums\InstallmentPaymentStatus;
use App\Models\Cart;
use App\Models\InstallmentContract;
use App\Models\InstallmentPayment;
use App\Models\Offer;
use App\Models\Order;
use App\Models\User;

class InstallmentScheduler
{
    public function createFromCheckout(Order $order, User $user, Offer $offer, Cart $cart): InstallmentContract
    {
        $cart->loadMissing(['items.offerProduct.offer']);

        $total = round($cart->items->sum(fn ($item) => $item->subtotal), 2);
        $selected = (int) ($cart->installment_months ?? 0);
        $months = max(2, $offer->hasPlan($selected) ? $selected : $offer->defaultPlanMonths());
        $amounts = $this->splitAmounts($total, $months);

        $contract = InstallmentContract::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'offer_id' => $offer->id,
            'months' => $months,
            'total_amount' => $total,
            'monthly_amount' => $amounts[0],
            'currency' => $order->currency ?? 'EGP',
            'status' => InstallmentContractStatus::Active,
            'offer_snapshot' => [
                'offer_id' => $offer->id,
                'name' => $offer->name,
                'slug' => $offer->slug,
                'installment_months' => $months,
            ],
        ]);

        $start = now()->startOfDay();

        foreach ($amounts as $index => $amount) {
            InstallmentPayment::create([
                'installment_contract_id' => $contract->id,
                'sequence' => $index + 1,
                'due_date' => $start->copy()->addMonths($index)->toDateString(),
                'amount' => $amount,
                'status' => InstallmentPaymentStatus::Pending,
            ]);
        }

        return $contract->load('installments');
    }

    /**
     * @return list<float>
     */
    public function splitAmounts(float $total, int $months): array
    {
        $months = max(1, $months);
        $monthly = round($total / $months, 2);
        $amounts = array_fill(0, $months - 1, $monthly);
        $amounts[] = round($total - ($monthly * ($months - 1)), 2);

        return $amounts;
    }

    public function refreshContractStatus(InstallmentContract $contract): InstallmentContract
    {
        $contract->load('installments');

        if ($contract->status === InstallmentContractStatus::Cancelled) {
            return $contract;
        }

        $open = $contract->installments->filter(
            fn (InstallmentPayment $row) => $row->status !== InstallmentPaymentStatus::Paid
        );

        if ($open->isEmpty()) {
            $contract->update(['status' => InstallmentContractStatus::Completed]);

            return $contract->fresh('installments');
        }

        $hasOverdue = $open->contains(
            fn (InstallmentPayment $row) => $row->status === InstallmentPaymentStatus::Overdue
                || ($row->status === InstallmentPaymentStatus::Pending && $row->due_date->lt(now()->startOfDay()))
        );

        $contract->update([
            'status' => $hasOverdue
                ? InstallmentContractStatus::Overdue
                : InstallmentContractStatus::Active,
        ]);

        return $contract->fresh('installments');
    }

    public function cancelForOrder(Order $order): void
    {
        $contracts = InstallmentContract::query()
            ->where('order_id', $order->id)
            ->whereNotIn('status', [InstallmentContractStatus::Cancelled->value, InstallmentContractStatus::Completed->value])
            ->with('installments')
            ->get();

        foreach ($contracts as $contract) {
            foreach ($contract->installments as $row) {
                if ($row->status !== InstallmentPaymentStatus::Paid) {
                    $row->update(['status' => InstallmentPaymentStatus::Failed]);
                }
            }

            $contract->update(['status' => InstallmentContractStatus::Cancelled]);
        }
    }
}
