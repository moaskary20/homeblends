<?php

namespace App\Repositories\Eloquent;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductRepository extends BaseRepository implements ProductRepositoryInterface
{
    public function __construct(Product $model)
    {
        parent::__construct($model);
    }

    public function paginate(int $perPage = 15, array $filters = []): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->with(['category', 'images', 'activeFlashSaleEntry.flashSale'])
            ->published();

        return $this->applyFilters($query, $filters)->paginate($perPage);
    }

    public function getFeatured(int $limit = 8): Collection
    {
        $query = fn () => $this->model->newQuery()
            ->with(['category', 'images', 'activeFlashSaleEntry.flashSale'])
            ->published()
            ->latest();

        $featured = (clone $query())
            ->featured()
            ->limit($limit)
            ->get();

        if ($featured->count() >= $limit) {
            return $featured;
        }

        $remaining = $limit - $featured->count();

        $additional = (clone $query())
            ->when($featured->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $featured->pluck('id')))
            ->limit($remaining)
            ->get();

        return $featured->concat($additional);
    }

    public function search(string $term, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->with(['category', 'images', 'variants', 'activeFlashSaleEntry.flashSale'])
            ->published()
            ->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%")
                    ->orWhere('short_description', 'like', "%{$term}%");
            });

        return $this->applyFilters($query, $filters)->paginate($perPage);
    }

    public function getByCategory(int $categoryId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->with(['category', 'images'])
            ->published()
            ->where('category_id', $categoryId)
            ->latest()
            ->paginate($perPage);
    }

    protected function applyFilters($query, array $filters)
    {
        if (! empty($filters['category_ids'])) {
            $query->whereIn('category_id', (array) $filters['category_ids']);
        } elseif (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (! empty($filters['q'])) {
            $term = $filters['q'];
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%")
                    ->orWhere('short_description', 'like', "%{$term}%");
            });
        }

        if (! empty($filters['featured'])) {
            $query->featured();
        }

        if (! empty($filters['in_stock'])) {
            $query->where(function ($q) {
                $q->where('stock_quantity', '>', 0)
                    ->orWhereHas('variants', fn ($vq) => $vq->where('stock_quantity', '>', 0));
            });
        }

        if (isset($filters['min_price']) && $filters['min_price'] !== null && $filters['min_price'] !== '') {
            $min = (float) $filters['min_price'];
            $query->where(function ($q) use ($min) {
                $q->where('regular_price', '>=', $min)
                    ->orWhereHas('variants', fn ($vq) => $vq->where('price', '>=', $min));
            });
        }

        if (isset($filters['max_price']) && $filters['max_price'] !== null && $filters['max_price'] !== '') {
            $max = (float) $filters['max_price'];
            $query->where(function ($q) use ($max) {
                $q->where('regular_price', '<=', $max)
                    ->orWhereHas('variants', fn ($vq) => $vq->where('price', '<=', $max));
            });
        }

        if (! empty($filters['attributes']) && is_array($filters['attributes'])) {
            foreach ($filters['attributes'] as $attributeId => $valueIds) {
                $query->where(function ($q) use ($attributeId, $valueIds) {
                    $q->whereHas('variants.attributeValues', function ($avq) use ($attributeId, $valueIds) {
                        $avq->where('attribute_id', $attributeId)
                            ->whereIn('attribute_value_id', $valueIds);
                    });
                });
            }
        }

        if (! empty($filters['sort'])) {
            match ($filters['sort']) {
                'price_asc' => $query->orderBy('regular_price'),
                'price_desc' => $query->orderByDesc('regular_price'),
                'name_asc' => $query->orderBy('name'),
                'newest' => $query->latest(),
                default => $query->latest(),
            };
        } else {
            $query->latest();
        }

        return $query;
    }
}
