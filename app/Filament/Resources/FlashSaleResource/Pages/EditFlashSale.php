<?php

namespace App\Filament\Resources\FlashSaleResource\Pages;

use App\Filament\Resources\FlashSaleResource;
use App\Services\FlashSale\FlashSaleProductSyncService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFlashSale extends EditRecord
{
    protected static string $resource = FlashSaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['flash_products'] = $this->record->products()
            ->with(['product', 'variant'])
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($entry) => [
                'id' => $entry->id,
                'product_id' => $entry->product_id,
                'product_variant_id' => $entry->product_variant_id,
                'sale_price' => $entry->sale_price,
                'stock_limit' => $entry->stock_limit,
                'sort_order' => $entry->sort_order,
            ])
            ->all();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->flashProducts = $data['flash_products'] ?? [];
        unset($data['flash_products']);

        return $data;
    }

    /** @var array<int, array<string, mixed>> */
    protected array $flashProducts = [];

    protected function afterSave(): void
    {
        app(FlashSaleProductSyncService::class)->sync($this->record, $this->flashProducts);
    }
}
