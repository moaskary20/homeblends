<?php

namespace App\Observers;

use App\Models\BundleItem;
use App\Services\Bundle\BundleService;

class BundleItemObserver
{
    public function saved(BundleItem $item): void
    {
        app(BundleService::class)->clearCaches();
    }

    public function deleted(BundleItem $item): void
    {
        app(BundleService::class)->clearCaches();
    }
}
