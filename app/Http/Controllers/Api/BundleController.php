<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductBundleResource;
use App\Models\ProductBundle;
use App\Services\Bundle\BundleService;

class BundleController extends Controller
{
    public function __construct(protected BundleService $bundles) {}

    public function index()
    {
        return ProductBundleResource::collection($this->bundles->getActiveBundles());
    }

    public function show(string $slug)
    {
        $bundle = ProductBundle::query()
            ->where('slug', $slug)
            ->with(['items.product.images', 'items.variant'])
            ->first();

        if (! $bundle || ! $bundle->isAvailable()) {
            return response()->json(['message' => __('Not found')], 404);
        }

        return new ProductBundleResource($bundle);
    }
}
