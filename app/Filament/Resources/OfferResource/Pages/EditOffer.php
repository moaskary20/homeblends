<?php

namespace App\Filament\Resources\OfferResource\Pages;

use App\Filament\Resources\OfferResource;
use App\Services\Offer\OfferProductSyncService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOffer extends EditRecord
{
    protected static string $resource = OfferResource::class;

    /** @var array<int, array<string, mixed>> */
    protected array $offerProducts = [];

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['offer_products'] = $this->record->products()
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($entry) => [
                'id' => $entry->id,
                'product_id' => $entry->product_id,
                'product_variant_id' => $entry->product_variant_id,
                'offer_price' => $entry->offer_price,
                'stock_limit' => $entry->stock_limit,
                'sort_order' => $entry->sort_order,
            ])
            ->all();

        $data['installment_plans'] = $this->record->planMonths();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->offerProducts = $data['offer_products'] ?? [];
        unset($data['offer_products']);

        return $data;
    }

    protected function afterSave(): void
    {
        app(OfferProductSyncService::class)->sync($this->record, $this->offerProducts);
    }
}
