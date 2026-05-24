<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlashSaleProduct extends Model
{
    protected $table = 'flash_sale_products';

    protected $fillable = [
        'flash_sale_id', 'product_id', 'product_variant_id',
        'sale_price', 'stock_limit', 'quantity_sold', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sale_price' => 'decimal:2',
        ];
    }

    protected function flashPrice(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->sale_price,
            set: fn ($value) => ['sale_price' => $value],
        );
    }

    protected function quantityLimit(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->stock_limit,
            set: fn ($value) => ['stock_limit' => $value],
        );
    }

    public function flashSale(): BelongsTo
    {
        return $this->belongsTo(FlashSale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function scopeInActiveSale($query)
    {
        return $query->whereHas('flashSale', fn ($q) => $q->active());
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

        return round((($compare - (float) $this->sale_price) / $compare) * 100, 1);
    }
}
