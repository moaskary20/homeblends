<?php

namespace App\Services\Affiliate;

use App\Enums\AffiliateStatus;
use App\Models\Affiliate;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AffiliateService
{
    public function apply(User $user, array $data): Affiliate
    {
        if ($user->affiliate) {
            throw ValidationException::withMessages([
                'email' => [__('ecommerce.affiliate_already_registered')],
            ]);
        }

        return DB::transaction(function () use ($user, $data) {
            $status = config('affiliate.auto_approve_applications')
                ? AffiliateStatus::Active
                : AffiliateStatus::Pending;

            $affiliate = Affiliate::create([
                'user_id' => $user->id,
                'code' => Affiliate::generateUniqueCode($data['display_name'] ?? $user->name),
                'display_name' => $data['display_name'] ?? $user->name,
                'status' => $status,
                'website' => $data['website'] ?? null,
                'bio' => $data['bio'] ?? null,
                'payment_details' => $data['payment_details'] ?? null,
                'approved_at' => $status === AffiliateStatus::Active ? now() : null,
            ]);

            if ($status === AffiliateStatus::Active) {
                $this->assignAffiliateRole($user);
            }

            return $affiliate;
        });
    }

    public function approve(Affiliate $affiliate, ?User $actor = null, ?float $commissionRate = null): Affiliate
    {
        $affiliate->update([
            'status' => AffiliateStatus::Active,
            'commission_rate' => $commissionRate ?? $affiliate->commission_rate,
            'approved_at' => now(),
            'approved_by' => $actor?->id,
        ]);

        $this->assignAffiliateRole($affiliate->user);

        return $affiliate->fresh();
    }

    public function suspend(Affiliate $affiliate, ?string $notes = null): Affiliate
    {
        $affiliate->update([
            'status' => AffiliateStatus::Suspended,
            'admin_notes' => $notes,
        ]);

        return $affiliate->fresh();
    }

    public function reject(Affiliate $affiliate, ?string $notes = null): Affiliate
    {
        $affiliate->update([
            'status' => AffiliateStatus::Rejected,
            'admin_notes' => $notes,
        ]);

        return $affiliate->fresh();
    }

    protected function assignAffiliateRole(User $user): void
    {
        if (! $user->hasRole('affiliate')) {
            $user->assignRole('affiliate');
        }
    }
}
