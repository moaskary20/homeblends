<?php

namespace App\Filament\Resources\FreeShippingRuleResource\Pages;

use App\Filament\Resources\FreeShippingRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFreeShippingRule extends EditRecord
{
    protected static string $resource = FreeShippingRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
