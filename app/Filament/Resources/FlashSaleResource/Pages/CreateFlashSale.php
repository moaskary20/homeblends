<?php

namespace App\Filament\Resources\FlashSaleResource\Pages;

use App\Filament\Resources\FlashSaleResource;
use App\Services\FlashSale\FlashSaleProductSyncService;
use Filament\Resources\Pages\CreateRecord;

class CreateFlashSale extends CreateRecord
{
    protected static string $resource = FlashSaleResource::class;

    /** @var array<int, array<string, mixed>> */
    protected array $flashProducts = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->flashProducts = $data['flash_products'] ?? [];
        unset($data['flash_products']);

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->flashProducts !== []) {
            app(FlashSaleProductSyncService::class)->sync($this->record, $this->flashProducts);
        }
    }
}
