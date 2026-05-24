<?php

namespace App\Models;

use App\Concerns\HasSlug;
use App\Enums\ProductStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Product extends Model
{
    use HasFactory, HasSlug, LogsActivity, SoftDeletes;

    protected $fillable = [
        'category_id', 'name', 'slug', 'sku', 'barcode',
        'short_description', 'full_description', 'main_image',
        'regular_price', 'discount_price', 'discount_starts_at', 'discount_ends_at', 'cost_price',
        'stock_quantity', 'low_stock_threshold', 'weight', 'dimensions',
        'status', 'is_featured', 'meta_title', 'meta_description',
        'avg_rating', 'reviews_count',
    ];

    protected function casts(): array
    {
        return [
            'regular_price' => 'decimal:2',
            'discount_price' => 'decimal:2',
            'discount_starts_at' => 'datetime',
            'discount_ends_at' => 'datetime',
            'cost_price' => 'decimal:2',
            'weight' => 'decimal:3',
            'is_featured' => 'boolean',
            'status' => ProductStatus::class,
            'avg_rating' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['name', 'status', 'stock_quantity', 'regular_price']);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    public function relatedProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_related', 'product_id', 'related_product_id');
    }

    public function flashSaleEntries(): HasMany
    {
        return $this->hasMany(FlashSaleProduct::class);
    }

    public function activeFlashSaleEntry(): HasOne
    {
        return $this->hasOne(FlashSaleProduct::class, 'product_id')
            ->inActiveSale()
            ->whereNull('product_variant_id')
            ->orderBy('sale_price');
    }

    public function scopePublished($query)
    {
        return $query->where('status', ProductStatus::Published);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function hasActiveTimedDiscount(): bool
    {
        if ($this->discount_price === null) {
            return false;
        }

        $now = now();

        if ($this->discount_starts_at && $now->lt($this->discount_starts_at)) {
            return false;
        }

        if ($this->discount_ends_at && $now->gt($this->discount_ends_at)) {
            return false;
        }

        return true;
    }

    public function baseSellingPrice(): float
    {
        if ($this->hasActiveTimedDiscount()) {
            return (float) $this->discount_price;
        }

        return (float) $this->regular_price;
    }

    public function getEffectivePriceAttribute(): float
    {
        if ($this->relationLoaded('activeFlashSaleEntry') && $this->activeFlashSaleEntry?->hasStock()) {
            return (float) $this->activeFlashSaleEntry->sale_price;
        }

        return $this->baseSellingPrice();
    }

    public function discountStatusLabel(): string
    {
        if ($this->discount_price === null) {
            return __('ecommerce.discount_none');
        }

        if ($this->hasActiveTimedDiscount()) {
            return __('ecommerce.discount_active');
        }

        if ($this->discount_starts_at && now()->lt($this->discount_starts_at)) {
            return __('ecommerce.discount_scheduled');
        }

        return __('ecommerce.discount_expired');
    }

    public function isOnFlashSale(): bool
    {
        return $this->relationLoaded('activeFlashSaleEntry')
            ? (bool) ($this->activeFlashSaleEntry?->hasStock())
            : app(\App\Services\FlashSale\FlashSaleService::class)->findActiveEntry($this) !== null;
    }

    public function isLowStock(): bool
    {
        return $this->stock_quantity <= $this->low_stock_threshold;
    }
}
