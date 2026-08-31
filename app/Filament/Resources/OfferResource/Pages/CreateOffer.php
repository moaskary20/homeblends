<?php

namespace App\Filament\Resources\OfferResource\Pages;

use App\Filament\Resources\OfferResource;
use App\Services\Offer\OfferProductSyncService;
use Filament\Resources\Pages\CreateRecord;

class CreateOffer extends CreateRecord
{
    protected static string $resource = OfferResource::class;

    /** @var array<int, array<string, mixed>> */
    protected array $offerProducts = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->offerProducts = $data['offer_products'] ?? [];
        unset($data['offer_products']);

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->offerProducts !== []) {
            app(OfferProductSyncService::class)->sync($this->record, $this->offerProducts);
        }
    }
}
