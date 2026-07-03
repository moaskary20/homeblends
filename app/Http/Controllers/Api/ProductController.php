<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Services\Shop\CategoryBrowseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    public function __construct(
        protected ProductRepositoryInterface $products,
        protected CategoryRepositoryInterface $categories,
        protected CategoryBrowseService $categoryBrowse,
    ) {}

    public function index(Request $request)
    {
        $filters = $this->resolveCategoryFilters(
            $request,
            $request->only(['category_id', 'category_slug', 'featured', 'min_price', 'max_price', 'sort']),
        );
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
        $filters = $this->resolveCategoryFilters(
            $request,
            $request->only(['category_id', 'category_slug', 'sort']),
        );
        $products = $this->products->search($request->q, $filters);

        return ProductResource::collection($products);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function resolveCategoryFilters(Request $request, array $filters): array
    {
        $category = null;

        if ($request->filled('category_slug')) {
            $category = $this->categories->findBySlug($request->string('category_slug'));
        } elseif (! empty($filters['category_id'])) {
            $category = $this->categories->find((int) $filters['category_id']);
        }

        if ($category !== null) {
            $filters['category_ids'] = $this->categoryBrowse->categoryIdsIncludingChildren($category);
        }

        unset($filters['category_id'], $filters['category_slug']);

        return $filters;
    }
}
