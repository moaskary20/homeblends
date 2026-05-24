<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnRequest extends Model
{
    protected $fillable = ['order_id', 'user_id', 'items', 'reason', 'status'];

    protected function casts(): array
    {
        return ['items' => 'array'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending' => __('ecommerce.return_pending'),
            'approved' => __('ecommerce.return_approved'),
            'rejected' => __('ecommerce.return_rejected'),
            'received' => __('ecommerce.return_received'),
            'completed' => __('ecommerce.return_completed'),
            default => $this->status,
        };
    }
}
