<?php

namespace App\Observers;

use App\Models\OfferProduct;
use App\Services\Offer\OfferService;

class OfferProductObserver
{
    public function saved(OfferProduct $entry): void
    {
        app(OfferService::class)->clearCaches();
    }

    public function deleted(OfferProduct $entry): void
    {
        app(OfferService::class)->clearCaches();
    }
}
