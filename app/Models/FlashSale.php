<?php

namespace App\Models;

use App\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FlashSale extends Model
{
    use HasSlug;

    protected $fillable = [
        'name', 'slug', 'description', 'starts_at', 'ends_at',
        'is_active', 'banner_image', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(FlashSaleProduct::class)->orderBy('sort_order');
    }

    public function scopeActive($query)
    {
        $now = now();

        return $query
            ->where('is_active', true)
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>=', $now);
    }

    public function scopeUpcoming($query)
    {
        return $query
            ->where('is_active', true)
            ->where('starts_at', '>', now());
    }

    public function isRunning(): bool
    {
        return $this->is_active
            && $this->starts_at <= now()
            && $this->ends_at >= now();
    }

    public function isUpcoming(): bool
    {
        return $this->is_active && $this->starts_at > now();
    }

    public function isEnded(): bool
    {
        return $this->ends_at < now();
    }

    public function statusLabel(): string
    {
        if (! $this->is_active) {
            return __('ecommerce.flash_sale_inactive');
        }

        if ($this->isRunning()) {
            return __('ecommerce.flash_sale_running');
        }

        if ($this->isUpcoming()) {
            return __('ecommerce.flash_sale_upcoming');
        }

        return __('ecommerce.flash_sale_ended');
    }
}
