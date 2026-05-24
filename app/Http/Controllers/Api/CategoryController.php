<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller
{
    public function __construct(
        protected CategoryRepositoryInterface $categories,
    ) {}

    public function index()
    {
        $tree = Cache::remember('categories.tree', config('ecommerce.cache.category_ttl'), function () {
            return $this->categories->getTree();
        });

        return CategoryResource::collection($tree);
    }

    public function show(string $slug)
    {
        $category = $this->categories->findBySlug($slug, ['children']);

        if (! $category) {
            return response()->json(['message' => __('Not found')], 404);
        }

        return new CategoryResource($category);
    }
}
