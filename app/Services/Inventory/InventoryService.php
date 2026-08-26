<?php

namespace App\Services\Inventory;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function available(Product $product, ?ProductVariant $variant = null): int
    {
        return max(0, (int) ($variant?->stock_quantity ?? $product->stock_quantity));
    }

    public function assertAvailable(Product $product, int $quantity, ?ProductVariant $variant = null): void
    {
        if ($quantity <= 0) {
            return;
        }

        if ($this->available($product, $variant) < $quantity) {
            throw ValidationException::withMessages([
                'quantity' => [__('ecommerce.insufficient_stock', [
                    'product' => $product->name,
                ])],
            ]);
        }
    }

    public function decrement(Product $product, int $quantity, ?ProductVariant $variant = null): void
    {
        if ($quantity <= 0) {
            return;
        }

        $this->assertAvailable($product->fresh(), $quantity, $variant?->fresh());

        $query = $variant
            ? ProductVariant::query()->whereKey($variant->id)
            : Product::query()->whereKey($product->id);

        $updated = $query
            ->where('stock_quantity', '>=', $quantity)
            ->decrement('stock_quantity', $quantity);

        if ($updated === 0) {
            throw ValidationException::withMessages([
                'quantity' => [__('ecommerce.insufficient_stock', [
                    'product' => $product->name,
                ])],
            ]);
        }

        if ($variant) {
            $variant->refresh();
        } else {
            $product->refresh();
        }
    }
}
