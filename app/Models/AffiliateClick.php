<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AffiliateClick extends Model
{
    protected $fillable = [
        'affiliate_id', 'session_id', 'ip_address', 'user_agent',
        'landing_url', 'referrer_url', 'converted', 'converted_at',
    ];

    protected function casts(): array
    {
        return [
            'converted' => 'boolean',
            'converted_at' => 'datetime',
        ];
    }

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function commission(): HasOne
    {
        return $this->hasOne(AffiliateCommission::class);
    }
}
