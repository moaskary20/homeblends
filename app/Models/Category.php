<?php

namespace App\Models;

use App\Concerns\HasSlug;
use App\Support\CategoryImageResolver;
use App\Support\ProductMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Category extends Model
{
    use HasFactory, HasSlug, LogsActivity, SoftDeletes;

    protected $fillable = [
        'parent_id', 'name', 'slug', 'image', 'description',
        'meta_title', 'meta_description', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('shop.nav.categories'));
        static::deleted(fn () => Cache::forget('shop.nav.categories'));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['name', 'is_active', 'parent_id']);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    public function imageUrl(?int $width = ProductMedia::SIZE_COLLECTION_THUMB): ?string
    {
        return ProductMedia::resizeUrl($this->effectiveImagePath(), $width);
    }

    public function effectiveImagePath(): ?string
    {
        if (filled($this->image)) {
            return $this->image;
        }

        foreach ([
            'images/categories/'.$this->slug.'.jpg',
            'images/categories/'.$this->slug.'.jpeg',
            'images/categories/'.$this->slug.'.png',
            'images/categories/'.$this->slug.'.webp',
            'images/categories/'.$this->slug.'.svg',
        ] as $candidate) {
            if (is_file(public_path($candidate))) {
                return $candidate;
            }
        }

        return app(CategoryImageResolver::class)->resolve(
            $this->slug,
            'images/categories/'.$this->slug.'.jpg',
        ) ?: $this->inheritParentImagePath();
    }

    protected function inheritParentImagePath(): ?string
    {
        $current = $this->parent_id
            ? ($this->relationLoaded('parent') ? $this->parent : $this->parent()->first())
            : null;

        while ($current !== null) {
            if (filled($current->image)) {
                return $current->image;
            }

            foreach (['jpg', 'jpeg', 'png', 'webp', 'svg'] as $extension) {
                $candidate = 'images/categories/'.$current->slug.'.'.$extension;
                if (is_file(public_path($candidate))) {
                    return $candidate;
                }
            }

            $current = $current->parent_id
                ? Category::query()->find($current->parent_id)
                : null;
        }

        return null;
    }

    public function usesVectorImage(): bool
    {
        return str_ends_with(strtolower((string) $this->effectiveImagePath()), '.svg');
    }
}
