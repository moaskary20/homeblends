<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BundleItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'product_bundle_id', 'product_id', 'product_variant_id', 'quantity', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    public function bundle(): BelongsTo
    {
        return $this->belongsTo(ProductBundle::class, 'product_bundle_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function lineRegularTotal(): float
    {
        $unit = (float) ($this->variant?->price ?? $this->product?->baseSellingPrice() ?? $this->product?->regular_price ?? 0);

        return round($unit * $this->quantity, 2);
    }
}
