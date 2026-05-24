<?php

namespace App\Observers;

use App\Models\FlashSale;
use App\Services\FlashSale\FlashSaleService;

class FlashSaleObserver
{
    public function saved(FlashSale $flashSale): void
    {
        app(FlashSaleService::class)->clearCaches();
    }

    public function deleted(FlashSale $flashSale): void
    {
        app(FlashSaleService::class)->clearCaches();
    }
}
