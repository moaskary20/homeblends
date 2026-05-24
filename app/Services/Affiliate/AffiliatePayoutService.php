<?php

namespace App\Services\Affiliate;

use App\Enums\AffiliateCommissionStatus;
use App\Enums\AffiliatePayoutStatus;
use App\Models\Affiliate;
use App\Models\AffiliateCommission;
use App\Models\AffiliatePayout;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AffiliatePayoutService
{
    public function request(Affiliate $affiliate, float $amount, ?string $notes = null): AffiliatePayout
    {
        $min = (float) config('affiliate.min_payout_amount');

        if ($amount < $min) {
            throw ValidationException::withMessages([
                'amount' => [__('ecommerce.affiliate_min_payout', ['amount' => $min])],
            ]);
        }

        if ($amount > (float) $affiliate->balance) {
            throw ValidationException::withMessages([
                'amount' => [__('ecommerce.affiliate_insufficient_balance')],
            ]);
        }

        $pending = $affiliate->payouts()
            ->whereIn('status', [AffiliatePayoutStatus::Pending, AffiliatePayoutStatus::Processing])
            ->exists();

        if ($pending) {
            throw ValidationException::withMessages([
                'amount' => [__('ecommerce.affiliate_payout_pending_exists')],
            ]);
        }

        return $affiliate->payouts()->create([
            'amount' => $amount,
            'currency' => config('ecommerce.currency', 'EGP'),
            'status' => AffiliatePayoutStatus::Pending,
            'notes' => $notes,
            'payment_method' => $affiliate->payment_details['method'] ?? null,
        ]);
    }

    public function markPaid(AffiliatePayout $payout, User $actor, ?string $reference = null, ?string $adminNotes = null): AffiliatePayout
    {
        return DB::transaction(function () use ($payout, $actor, $reference, $adminNotes) {
            if ($payout->status === AffiliatePayoutStatus::Paid) {
                return $payout;
            }

            $affiliate = $payout->affiliate;

            if ((float) $affiliate->balance < (float) $payout->amount) {
                throw ValidationException::withMessages([
                    'amount' => [__('ecommerce.affiliate_insufficient_balance')],
                ]);
            }

            $affiliate->decrement('balance', $payout->amount);
            $affiliate->increment('total_paid', $payout->amount);

            $payout->update([
                'status' => AffiliatePayoutStatus::Paid,
                'payment_reference' => $reference,
                'admin_notes' => $adminNotes,
                'processed_by' => $actor->id,
                'processed_at' => now(),
            ]);

            $this->markCommissionsPaid($affiliate, (float) $payout->amount);

            return $payout->fresh();
        });
    }

    public function reject(AffiliatePayout $payout, User $actor, ?string $adminNotes = null): AffiliatePayout
    {
        $payout->update([
            'status' => AffiliatePayoutStatus::Rejected,
            'admin_notes' => $adminNotes,
            'processed_by' => $actor->id,
            'processed_at' => now(),
        ]);

        return $payout->fresh();
    }

    protected function markCommissionsPaid(Affiliate $affiliate, float $payoutAmount): void
    {
        $remaining = $payoutAmount;

        $commissions = $affiliate->commissions()
            ->where('status', AffiliateCommissionStatus::Approved)
            ->orderBy('id')
            ->get();

        foreach ($commissions as $commission) {
            if ($remaining <= 0) {
                break;
            }

            app(AffiliateCommissionService::class)->markPaid($commission);
            $remaining -= (float) $commission->commission_amount;
        }
    }
}
