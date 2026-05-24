<?php

namespace App\Observers;

use App\Models\ProductBundle;
use App\Services\Bundle\BundleService;

class ProductBundleObserver
{
    public function saved(ProductBundle $bundle): void
    {
        app(BundleService::class)->clearCaches();
    }

    public function deleted(ProductBundle $bundle): void
    {
        app(BundleService::class)->clearCaches();
    }
}
