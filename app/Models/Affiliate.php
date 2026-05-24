<?php

namespace App\Models;

use App\Enums\AffiliateStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Affiliate extends Model
{
    protected $fillable = [
        'user_id', 'code', 'display_name', 'status', 'commission_rate',
        'website', 'bio', 'payment_details', 'balance', 'total_earned',
        'total_paid', 'total_clicks', 'total_orders', 'approved_at',
        'approved_by', 'admin_notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => AffiliateStatus::class,
            'commission_rate' => 'decimal:2',
            'payment_details' => 'array',
            'balance' => 'decimal:2',
            'total_earned' => 'decimal:2',
            'total_paid' => 'decimal:2',
            'approved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(AffiliateClick::class);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(AffiliateCommission::class);
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(AffiliatePayout::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', AffiliateStatus::Active);
    }

    public function isActive(): bool
    {
        return $this->status === AffiliateStatus::Active;
    }

    public function effectiveCommissionRate(): float
    {
        return (float) ($this->commission_rate ?? config('affiliate.default_commission_rate'));
    }

    public function referralUrl(): string
    {
        return route('shop.home', ['ref' => $this->code]);
    }

    public static function generateUniqueCode(string $base): string
    {
        $slug = Str::upper(Str::slug(Str::limit($base, 20, '')));
        $slug = $slug !== '' ? $slug : 'HB';
        $code = $slug;
        $attempt = 0;

        while (static::where('code', $code)->exists()) {
            $attempt++;
            $code = $slug.$attempt;
        }

        return $code;
    }
}
