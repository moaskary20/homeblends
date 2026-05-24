<?php

namespace App\Services\Shop;

use App\Models\Category;
use App\Models\Product;

class ProductCatalogExporter
{
    /**
     * @return array{categories: array<int, array<string, mixed>>, products: array<int, array<string, mixed>>}
     */
    public function export(): array
    {
        $products = Product::withTrashed()
            ->with(['category.parent', 'images'])
            ->orderBy('sku')
            ->get();

        $categoryIds = $products->pluck('category_id')->unique()->filter();
        $parentIds = Category::query()
            ->whereIn('id', $categoryIds)
            ->pluck('parent_id')
            ->filter();
        $allCategoryIds = $categoryIds->merge($parentIds)->unique();

        $categories = Category::withTrashed()
            ->with('parent')
            ->whereIn('id', $allCategoryIds)
            ->orderBy('parent_id')
            ->orderBy('sort_order')
            ->get();

        return [
            'exported_at' => now()->toIso8601String(),
            'categories' => $categories->map(fn (Category $category): array => [
                'slug' => $category->slug,
                'name' => $category->name,
                'parent_slug' => $category->parent?->slug,
                'description' => $category->description,
                'image' => $category->image,
                'meta_title' => $category->meta_title,
                'meta_description' => $category->meta_description,
                'is_active' => $category->is_active,
                'sort_order' => $category->sort_order,
            ])->values()->all(),
            'products' => $products->map(fn (Product $product): array => [
                'sku' => $product->sku,
                'name' => $product->name,
                'slug' => $product->slug,
                'category_slug' => $product->category?->slug,
                'barcode' => $product->barcode,
                'short_description' => $product->short_description,
                'full_description' => $product->full_description,
                'regular_price' => (float) $product->regular_price,
                'discount_price' => $product->discount_price !== null ? (float) $product->discount_price : null,
                'discount_starts_at' => $product->discount_starts_at?->toIso8601String(),
                'discount_ends_at' => $product->discount_ends_at?->toIso8601String(),
                'cost_price' => $product->cost_price !== null ? (float) $product->cost_price : null,
                'stock_quantity' => $product->stock_quantity,
                'low_stock_threshold' => $product->low_stock_threshold,
                'weight' => $product->weight !== null ? (float) $product->weight : null,
                'dimensions' => $product->dimensions,
                'status' => $product->status->value ?? (string) $product->status,
                'is_featured' => $product->is_featured,
                'main_image' => $product->main_image,
                'meta_title' => $product->meta_title,
                'meta_description' => $product->meta_description,
                'images' => $product->images->map(fn ($image): array => [
                    'path' => $image->path,
                    'alt' => $image->alt,
                    'sort_order' => $image->sort_order,
                ])->values()->all(),
            ])->values()->all(),
        ];
    }
}
