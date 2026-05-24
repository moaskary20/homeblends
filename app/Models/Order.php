<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'order_number', 'user_id', 'status', 'billing_address', 'shipping_address',
        'shipping_rate_id', 'shipping_method', 'subtotal', 'discount_amount',
        'shipping_amount', 'tax_amount', 'total', 'currency', 'coupon_id',
        'loyalty_points_earned', 'loyalty_points_redeemed', 'notes',
        'tracking_number', 'payment_method', 'payment_status', 'paid_at',
        'affiliate_id', 'affiliate_click_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'billing_address' => 'array',
            'shipping_address' => 'array',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'shipping_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function refundRequests(): HasMany
    {
        return $this->hasMany(RefundRequest::class);
    }

    public function returnRequests(): HasMany
    {
        return $this->hasMany(ReturnRequest::class);
    }

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function affiliateClick(): BelongsTo
    {
        return $this->belongsTo(AffiliateClick::class, 'affiliate_click_id');
    }

    public function affiliateCommission(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(AffiliateCommission::class);
    }
}
