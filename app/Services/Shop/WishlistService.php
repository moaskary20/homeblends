<?php

namespace App\Services\Shop;

use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Support\Collection;

class WishlistService
{
    public function count(User $user): int
    {
        return Wishlist::query()->where('user_id', $user->id)->count();
    }

    /**
     * @return array<int, int>
     */
    public function productIds(User $user): array
    {
        return Wishlist::query()
            ->where('user_id', $user->id)
            ->pluck('product_id')
            ->all();
    }

    public function products(User $user): Collection
    {
        return Wishlist::query()
            ->where('user_id', $user->id)
            ->with(['product.category', 'product.images'])
            ->latest()
            ->get()
            ->pluck('product')
            ->filter();
    }

    public function previewProducts(User $user, int $limit = 5): Collection
    {
        return Wishlist::query()
            ->where('user_id', $user->id)
            ->with(['product.images'])
            ->latest()
            ->limit($limit)
            ->get()
            ->pluck('product')
            ->filter();
    }

    public function toggle(User $user, Product $product): bool
    {
        $existing = Wishlist::query()
            ->where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->first();

        if ($existing) {
            $existing->delete();

            return false;
        }

        Wishlist::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        return true;
    }

    public function has(User $user, Product $product): bool
    {
        return Wishlist::query()
            ->where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->exists();
    }

    public function remove(User $user, Product $product): void
    {
        Wishlist::query()
            ->where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->delete();
    }
}
