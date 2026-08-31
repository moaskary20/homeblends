<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OfferProduct extends Model
{
    protected $fillable = [
        'offer_id', 'product_id', 'product_variant_id',
        'offer_price', 'stock_limit', 'quantity_sold', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'offer_price' => 'decimal:2',
        ];
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function scopeInActiveOffer($query)
    {
        return $query->whereHas('offer', fn ($q) => $q->active());
    }

    public function remainingQuantity(): ?int
    {
        if ($this->stock_limit === null) {
            return null;
        }

        return max(0, $this->stock_limit - $this->quantity_sold);
    }

    public function hasStock(int $quantity = 1): bool
    {
        $remaining = $this->remainingQuantity();

        return $remaining === null || $quantity <= $remaining;
    }

    public function discountPercent(): float
    {
        $compare = $this->variant?->price ?? $this->product?->regular_price ?? 0;

        if ($compare <= 0) {
            return 0;
        }

        return round((($compare - (float) $this->offer_price) / $compare) * 100, 1);
    }

    public function comparePrice(): float
    {
        return (float) ($this->variant?->price ?? $this->product?->regular_price ?? 0);
    }

    public function monthlyAmount(): float
    {
        $months = max(1, $this->offer?->defaultPlanMonths() ?? 1);

        return round((float) $this->offer_price / $months, 2);
    }

    /**
     * @return array<string, mixed>
     */
    public function toSnapshot(): array
    {
        $this->loadMissing(['offer', 'product', 'variant']);

        return [
            'offer_id' => $this->offer_id,
            'offer_product_id' => $this->id,
            'offer_name' => $this->offer?->name,
            'offer_slug' => $this->offer?->slug,
            'installment_months' => $this->offer?->defaultPlanMonths(),
            'installment_plans' => $this->offer?->planMonths(),
            'offer_price' => (float) $this->offer_price,
        ];
    }
}
