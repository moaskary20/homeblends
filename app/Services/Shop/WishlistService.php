<?php

namespace App\Services\Shop;

use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use App\Services\Shop\Concerns\ResolvesGuestShopLists;
use Illuminate\Support\Collection;

class WishlistService
{
    use ResolvesGuestShopLists;

    public function count(?User $user = null, ?string $sessionId = null): int
    {
        return $this->customerQuery(Wishlist::class, $user, $sessionId)->count();
    }

    /**
     * @return array<int, int>
     */
    public function productIds(?User $user = null, ?string $sessionId = null): array
    {
        return $this->customerQuery(Wishlist::class, $user, $sessionId)
            ->pluck('product_id')
            ->all();
    }

    public function products(?User $user = null, ?string $sessionId = null): Collection
    {
        return $this->customerQuery(Wishlist::class, $user, $sessionId)
            ->with(['product.category', 'product.images'])
            ->latest()
            ->get()
            ->pluck('product')
            ->filter();
    }

    public function previewProducts(?User $user = null, ?string $sessionId = null, int $limit = 5): Collection
    {
        return $this->customerQuery(Wishlist::class, $user, $sessionId)
            ->with(['product.images'])
            ->latest()
            ->limit($limit)
            ->get()
            ->pluck('product')
            ->filter();
    }

    public function toggle(?User $user, ?string $sessionId, Product $product): bool
    {
        [$user, $sessionId] = $this->resolveCustomer($user, $sessionId);

        $existing = $this->customerQuery(Wishlist::class, $user, $sessionId)
            ->where('product_id', $product->id)
            ->first();

        if ($existing) {
            $existing->delete();

            return false;
        }

        Wishlist::create([
            'user_id' => $user?->id,
            'session_id' => $user ? null : $sessionId,
            'product_id' => $product->id,
        ]);

        return true;
    }

    public function has(?User $user, ?string $sessionId, Product $product): bool
    {
        return $this->customerQuery(Wishlist::class, $user, $sessionId)
            ->where('product_id', $product->id)
            ->exists();
    }

    public function remove(?User $user, ?string $sessionId, Product $product): void
    {
        $this->customerQuery(Wishlist::class, $user, $sessionId)
            ->where('product_id', $product->id)
            ->delete();
    }

    public function mergeGuestToUser(string $sessionId, int $userId): void
    {
        $guestItems = Wishlist::query()
            ->whereNull('user_id')
            ->where('session_id', $sessionId)
            ->get();

        foreach ($guestItems as $item) {
            Wishlist::query()->updateOrCreate(
                [
                    'user_id' => $userId,
                    'product_id' => $item->product_id,
                ],
                [
                    'session_id' => null,
                ]
            );

            $item->delete();
        }
    }
}
