<?php

namespace App\Observers;

use App\Models\Offer;
use App\Services\Offer\OfferService;

class OfferObserver
{
    public function saved(Offer $offer): void
    {
        app(OfferService::class)->clearCaches();
    }

    public function deleted(Offer $offer): void
    {
        app(OfferService::class)->clearCaches();
    }
}
