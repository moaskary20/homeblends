<?php

namespace App\Models;

use App\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductBundle extends Model
{
    use HasSlug;

    protected $fillable = [
        'name', 'slug', 'short_description', 'description', 'main_image',
        'bundle_price', 'starts_at', 'ends_at', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'bundle_price' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(BundleItem::class, 'product_bundle_id')->orderBy('sort_order');
    }

    public function scopeActive($query)
    {
        $now = now();

        return $query
            ->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            });
    }

    public function isAvailable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();

        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->ends_at && $now->gt($this->ends_at)) {
            return false;
        }

        return $this->items()->exists();
    }

    public function statusLabel(): string
    {
        if (! $this->is_active) {
            return __('ecommerce.bundle_inactive');
        }

        if ($this->isAvailable()) {
            return __('ecommerce.bundle_active');
        }

        if ($this->starts_at && now()->lt($this->starts_at)) {
            return __('ecommerce.bundle_scheduled');
        }

        return __('ecommerce.bundle_expired');
    }

    public function toSnapshot(): array
    {
        $this->loadMissing(['items.product', 'items.variant']);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'bundle_price' => (float) $this->bundle_price,
            'items' => $this->items->map(fn (BundleItem $item) => [
                'product_id' => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'product_name' => $item->product->name,
                'sku' => $item->variant?->sku ?? $item->product->sku,
                'quantity' => $item->quantity,
            ])->values()->all(),
        ];
    }
}
