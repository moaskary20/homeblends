<?php

namespace App\Models;

use App\Enums\InstallmentPaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstallmentPayment extends Model
{
    protected $fillable = [
        'installment_contract_id', 'sequence', 'due_date', 'amount', 'status',
        'paid_at', 'payment_id', 'pre_due_reminded_at', 'due_reminded_at', 'overdue_reminded_at',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'amount' => 'decimal:2',
            'status' => InstallmentPaymentStatus::class,
            'paid_at' => 'datetime',
            'pre_due_reminded_at' => 'datetime',
            'due_reminded_at' => 'datetime',
            'overdue_reminded_at' => 'datetime',
        ];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(InstallmentContract::class, 'installment_contract_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function isPayable(): bool
    {
        return in_array($this->status, [
            InstallmentPaymentStatus::Pending,
            InstallmentPaymentStatus::Overdue,
        ], true);
    }
}
