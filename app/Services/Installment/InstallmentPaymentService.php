<?php

namespace App\Services\Installment;

use App\Enums\InstallmentContractStatus;
use App\Enums\InstallmentPaymentStatus;
use App\Enums\PaymentGateway as PaymentGatewayDriver;
use App\Models\InstallmentContract;
use App\Models\InstallmentPayment;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Payment\PaymentGatewayService;
use App\Services\Payment\PaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InstallmentPaymentService
{
    public function __construct(
        protected PaymentService $payments,
        protected PaymentGatewayService $gateways,
        protected InstallmentScheduler $scheduler,
    ) {}

    public function dueNowAmount(InstallmentContract $contract, float $shippingAndFees = 0): float
    {
        $first = $contract->installments->firstWhere('sequence', 1);

        return round(((float) ($first?->amount ?? $contract->monthly_amount)) + $shippingAndFees, 2);
    }

    public function markPaid(InstallmentPayment $installment, ?Payment $payment = null): InstallmentPayment
    {
        return DB::transaction(function () use ($installment, $payment) {
            $installment->refresh();

            if ($installment->status === InstallmentPaymentStatus::Paid) {
                return $installment;
            }

            $installment->update([
                'status' => InstallmentPaymentStatus::Paid,
                'paid_at' => now(),
                'payment_id' => $payment?->id ?? $installment->payment_id,
            ]);

            if ($payment && $payment->installment_payment_id !== $installment->id) {
                $payment->update(['installment_payment_id' => $installment->id]);
            }

            $contract = $this->scheduler->refreshContractStatus($installment->contract()->first());
            $this->syncOrderPaymentStatus($contract->order);

            return $installment->fresh(['contract', 'payment']);
        });
    }

    public function initiateCustomerPayment(
        InstallmentPayment $installment,
        PaymentGatewayDriver $gateway,
    ): Payment {
        if ($gateway === PaymentGatewayDriver::CashOnDelivery) {
            throw ValidationException::withMessages([
                'payment_gateway' => [__('ecommerce.installment_cod_forbidden')],
            ]);
        }

        if (! $installment->isPayable()) {
            throw ValidationException::withMessages([
                'installment' => [__('ecommerce.installment_not_payable')],
            ]);
        }

        $contract = $installment->contract()->with('order')->first();
        $next = $contract?->nextUnpaid();

        if (! $contract?->isOpen() || ! $next || $next->id !== $installment->id) {
            throw ValidationException::withMessages([
                'installment' => [__('ecommerce.installment_pay_in_order')],
            ]);
        }

        $gatewayConfig = $this->gateways->assertAvailableForOrder(
            $gateway->value,
            (float) $installment->amount
        );

        $payment = $this->payments->initiate(
            $contract->order,
            $gateway,
            [
                'gateway_name' => $gatewayConfig->displayName(),
                'installment_sequence' => $installment->sequence,
            ],
            amount: (float) $installment->amount,
            installmentPaymentId: $installment->id,
        );

        if ($payment->status === 'completed') {
            $this->markPaid($installment, $payment);
        }

        return $payment;
    }

    public function attachCheckoutPayment(InstallmentContract $contract, Payment $payment): void
    {
        $first = $contract->installments->firstWhere('sequence', 1);

        if (! $first) {
            return;
        }

        $first->update(['payment_id' => $payment->id]);
        $payment->update(['installment_payment_id' => $first->id]);

        if ($payment->status === 'completed') {
            $this->markPaid($first, $payment);
        }
    }

    public function syncOrderPaymentStatus(?Order $order): void
    {
        if (! $order) {
            return;
        }

        $contracts = InstallmentContract::query()
            ->where('order_id', $order->id)
            ->with('installments')
            ->get();

        if ($contracts->isEmpty()) {
            return;
        }

        $open = $contracts->filter(fn (InstallmentContract $c) => $c->status !== InstallmentContractStatus::Cancelled);

        if ($open->every(fn (InstallmentContract $c) => $c->status === InstallmentContractStatus::Completed)) {
            $order->update([
                'payment_status' => 'paid',
                'paid_at' => $order->paid_at ?? now(),
            ]);

            return;
        }

        $anyPaid = $open->contains(fn (InstallmentContract $c) => $c->paidTotal() > 0);

        $order->update([
            'payment_status' => $anyPaid ? 'partial' : 'pending',
        ]);
    }
}
