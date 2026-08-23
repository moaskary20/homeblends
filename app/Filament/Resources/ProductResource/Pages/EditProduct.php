<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            ->submit(null)
            ->requiresConfirmation()
            ->modalHeading(__('ecommerce.confirm_update_product_heading'))
            ->modalDescription(__('ecommerce.confirm_update_product_description'))
            ->modalSubmitActionLabel(__('ecommerce.confirm_update_product_submit'))
            ->action('save');
    }
}
