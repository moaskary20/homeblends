<?php

namespace App\Observers;

use App\Models\FlashSaleProduct;
use App\Services\FlashSale\FlashSaleService;

class FlashSaleProductObserver
{
    public function saved(FlashSaleProduct $entry): void
    {
        app(FlashSaleService::class)->clearCaches();
    }

    public function deleted(FlashSaleProduct $entry): void
    {
        app(FlashSaleService::class)->clearCaches();
    }
}
