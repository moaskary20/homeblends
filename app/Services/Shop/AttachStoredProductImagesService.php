<?php

namespace App\Services\Shop;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachStoredProductImagesService
{
    public function attachAll(): int
    {
        $attached = 0;

        foreach (Product::query()->orderBy('sku')->get() as $product) {
            if ($this->attachForProduct($product)) {
                $attached++;
            }
        }

        return $attached;
    }

    public function attachForProduct(Product $product): bool
    {
        $paths = $this->discoverPaths($product->sku);

        if ($paths === []) {
            return false;
        }

        $product->images()->delete();

        foreach ($paths as $sort => $path) {
            ProductImage::create([
                'product_id' => $product->id,
                'path' => $path,
                'alt' => $product->name,
                'sort_order' => $sort,
            ]);
        }

        $product->update(['main_image' => $paths[0]]);

        return true;
    }

    /**
     * @return array<int, string>
     */
    protected function discoverPaths(string $sku): array
    {
        $disk = Storage::disk('public');
        $prefix = Str::slug($sku);
        $directory = 'products/scraped';

        if (! $disk->exists($directory)) {
            return [];
        }

        $matches = collect($disk->files($directory))
            ->filter(function (string $path) use ($prefix): bool {
                $basename = pathinfo($path, PATHINFO_FILENAME);

                return $basename === $prefix
                    || str_starts_with($basename, $prefix.'-');
            })
            ->sort(function (string $a, string $b) use ($prefix): int {
                return $this->imageSortKey($a, $prefix) <=> $this->imageSortKey($b, $prefix);
            })
            ->values()
            ->all();

        return $matches;
    }

    protected function imageSortKey(string $path, string $prefix): int
    {
        $basename = pathinfo($path, PATHINFO_FILENAME);

        if ($basename === $prefix) {
            return 0;
        }

        $suffix = Str::after($basename, $prefix.'-');

        return is_numeric($suffix) ? (int) $suffix + 1 : 999;
    }
}
