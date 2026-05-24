<?php

namespace App\Filament\Affiliate\Resources\PayoutResource\Pages;

use App\Filament\Affiliate\Resources\PayoutResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPayouts extends ListRecords
{
    protected static string $resource = PayoutResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label(__('ecommerce.request_payout')),
        ];
    }
}
