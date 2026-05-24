<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    public function __construct(
        protected ProductRepositoryInterface $products,
    ) {}

    public function index(Request $request)
    {
        $filters = $request->only(['category_id', 'featured', 'min_price', 'max_price', 'sort']);
        $products = $this->products->paginate($request->integer('per_page', 15), $filters);

        return ProductResource::collection($products);
    }

    public function featured()
    {
        $products = Cache::remember('products.featured', config('ecommerce.cache.product_ttl'), function () {
            return $this->products->getFeatured(12);
        });

        return ProductResource::collection($products);
    }

    public function show(string $slug)
    {
        $product = Cache::remember("products.{$slug}", config('ecommerce.cache.product_ttl'), function () use ($slug) {
            return $this->products->findBySlug($slug, [
                'category', 'images', 'variants',
                'activeFlashSaleEntry.flashSale',
                'reviews' => fn ($q) => $q->approved(),
            ]);
        });

        if (! $product) {
            return response()->json(['message' => __('Not found')], 404);
        }

        return new ProductResource($product);
    }

    public function search(Request $request)
    {
        $request->validate(['q' => 'required|string|min:2']);
        $products = $this->products->search($request->q, $request->only(['category_id', 'sort']));

        return ProductResource::collection($products);
    }
}
