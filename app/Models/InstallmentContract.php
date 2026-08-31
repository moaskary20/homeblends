<?php

namespace App\Models;

use App\Enums\InstallmentContractStatus;
use App\Enums\InstallmentPaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstallmentContract extends Model
{
    protected $fillable = [
        'order_id', 'user_id', 'offer_id', 'months', 'total_amount',
        'monthly_amount', 'currency', 'status', 'offer_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'monthly_amount' => 'decimal:2',
            'status' => InstallmentContractStatus::class,
            'offer_snapshot' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function installments(): HasMany
    {
        return $this->hasMany(InstallmentPayment::class)->orderBy('sequence');
    }

    public function paidTotal(): float
    {
        return (float) $this->installments
            ->where('status', InstallmentPaymentStatus::Paid)
            ->sum('amount');
    }

    public function remainingTotal(): float
    {
        return max(0, round((float) $this->total_amount - $this->paidTotal(), 2));
    }

    public function nextUnpaid(): ?InstallmentPayment
    {
        return $this->installments
            ->first(fn (InstallmentPayment $row) => $row->status !== InstallmentPaymentStatus::Paid
                && $row->status !== InstallmentPaymentStatus::Failed);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [
            InstallmentContractStatus::Active,
            InstallmentContractStatus::Overdue,
        ], true);
    }
}
