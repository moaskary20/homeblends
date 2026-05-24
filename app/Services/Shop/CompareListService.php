<?php

namespace App\Services\Shop;

use App\Models\CompareList;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;

class CompareListService
{
    public function maxItems(): int
    {
        return (int) config('ecommerce.compare.max_items', 4);
    }

    public function count(User $user): int
    {
        return CompareList::query()->where('user_id', $user->id)->count();
    }

    /**
     * @return array<int, int>
     */
    public function productIds(User $user): array
    {
        return CompareList::query()
            ->where('user_id', $user->id)
            ->pluck('product_id')
            ->all();
    }

    public function products(User $user): Collection
    {
        return CompareList::query()
            ->where('user_id', $user->id)
            ->with([
                'product.category',
                'product.images',
                'product.variants',
                'product.activeFlashSaleEntry',
            ])
            ->oldest()
            ->get()
            ->pluck('product')
            ->filter();
    }

    public function remove(User $user, Product $product): void
    {
        CompareList::query()
            ->where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->delete();
    }

    public function toggle(User $user, Product $product): bool
    {
        $existing = CompareList::query()
            ->where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->first();

        if ($existing) {
            $existing->delete();

            return false;
        }

        if ($this->count($user) >= $this->maxItems()) {
            throw new \RuntimeException(__('ecommerce.compare_max_reached', ['max' => $this->maxItems()]));
        }

        CompareList::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        return true;
    }

    public function has(User $user, Product $product): bool
    {
        return CompareList::query()
            ->where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->exists();
    }

    public function clear(User $user): void
    {
        CompareList::query()->where('user_id', $user->id)->delete();
    }
}
