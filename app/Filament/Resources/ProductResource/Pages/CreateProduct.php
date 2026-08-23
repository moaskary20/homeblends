<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->submit(null)
            ->requiresConfirmation()
            ->modalHeading(__('ecommerce.confirm_create_product_heading'))
            ->modalDescription(__('ecommerce.confirm_create_product_description'))
            ->modalSubmitActionLabel(__('ecommerce.confirm_create_product_submit'))
            ->action('create');
    }

    protected function getCreateAnotherFormAction(): Action
    {
        return parent::getCreateAnotherFormAction()
            ->requiresConfirmation()
            ->modalHeading(__('ecommerce.confirm_create_another_product_heading'))
            ->modalDescription(__('ecommerce.confirm_create_another_product_description'))
            ->modalSubmitActionLabel(__('ecommerce.confirm_create_another_product_submit'));
    }
}
