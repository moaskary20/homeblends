<?php

namespace App\Services\Shop;

use App\Models\CompareList;
use App\Models\Product;
use App\Models\User;
use App\Services\Shop\Concerns\ResolvesGuestShopLists;
use Illuminate\Support\Collection;

class CompareListService
{
    use ResolvesGuestShopLists;

    public function maxItems(): int
    {
        return (int) config('ecommerce.compare.max_items', 4);
    }

    public function count(?User $user = null, ?string $sessionId = null): int
    {
        return $this->customerQuery(CompareList::class, $user, $sessionId)->count();
    }

    /**
     * @return array<int, int>
     */
    public function productIds(?User $user = null, ?string $sessionId = null): array
    {
        return $this->customerQuery(CompareList::class, $user, $sessionId)
            ->pluck('product_id')
            ->all();
    }

    public function products(?User $user = null, ?string $sessionId = null): Collection
    {
        return $this->customerQuery(CompareList::class, $user, $sessionId)
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

    public function remove(?User $user, ?string $sessionId, Product $product): void
    {
        $this->customerQuery(CompareList::class, $user, $sessionId)
            ->where('product_id', $product->id)
            ->delete();
    }

    public function toggle(?User $user, ?string $sessionId, Product $product): bool
    {
        [$user, $sessionId] = $this->resolveCustomer($user, $sessionId);

        if (! $user) {
            $sessionId = $this->assertGuestSessionId($sessionId);
        }

        $existing = $this->customerQuery(CompareList::class, $user, $sessionId)
            ->where('product_id', $product->id)
            ->first();

        if ($existing) {
            $existing->delete();

            return false;
        }

        if ($this->count($user, $sessionId) >= $this->maxItems()) {
            throw new \RuntimeException(__('ecommerce.compare_max_reached', ['max' => $this->maxItems()]));
        }

        CompareList::create([
            'user_id' => $user?->id,
            'session_id' => $user ? null : $sessionId,
            'product_id' => $product->id,
        ]);

        return true;
    }

    public function has(?User $user, ?string $sessionId, Product $product): bool
    {
        return $this->customerQuery(CompareList::class, $user, $sessionId)
            ->where('product_id', $product->id)
            ->exists();
    }

    public function clear(?User $user = null, ?string $sessionId = null): void
    {
        [$user, $sessionId] = $this->resolveCustomer($user, $sessionId);

        if (! $user) {
            $sessionId = $this->assertGuestSessionId($sessionId);
        }

        $this->customerQuery(CompareList::class, $user, $sessionId)->delete();
    }

    public function mergeGuestToUser(string $sessionId, int $userId): void
    {
        $guestItems = CompareList::query()
            ->whereNull('user_id')
            ->where('session_id', $sessionId)
            ->get();

        foreach ($guestItems as $item) {
            if (CompareList::query()->where('user_id', $userId)->count() >= $this->maxItems()) {
                break;
            }

            CompareList::query()->updateOrCreate(
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
