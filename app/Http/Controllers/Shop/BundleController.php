<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\ProductBundle;
use App\Services\Bundle\BundleService;
use App\Services\Seo\SeoService;

class BundleController extends Controller
{
    public function index(BundleService $bundles)
    {
        return view('shop.bundles.index', [
            'bundles' => $bundles->getActiveBundles(),
        ]);
    }

    public function show(string $slug, BundleService $bundleService)
    {
        $bundle = ProductBundle::query()
            ->where('slug', $slug)
            ->with(['items.product.images', 'items.variant'])
            ->firstOrFail();

        abort_unless($bundle->isAvailable(), 404);

        return view('shop.bundles.show', [
            'bundle' => $bundle,
            'regularTotal' => $bundleService->calculateRegularTotal($bundle),
            'savings' => $bundleService->calculateSavings($bundle),
            'savingsPercent' => $bundleService->savingsPercent($bundle),
            'seo' => app(SeoService::class)->forBundle($bundle),
        ]);
    }
}
