<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Filament\Resources\OrderResource\Concerns\HasInvoiceActions;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    use HasInvoiceActions;

    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...static::getInvoiceHeaderActions(),
            Actions\EditAction::make(),
        ];
    }
}
