<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Enums\ProductStatus;
use App\Filament\Resources\ProductResource;
use App\Models\Product;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    public bool $isCreating = false;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        $this->isCreating = request()->boolean('new') || $this->isUnfinishedCreateDraft();
    }

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    public function getTitle(): string | Htmlable
    {
        if ($this->isCreating) {
            return __('ecommerce.add_new_product');
        }

        return parent::getTitle();
    }

    public function getHeading(): string | Htmlable
    {
        if ($this->isCreating) {
            return __('ecommerce.add_new_product');
        }

        return parent::getHeading();
    }

    public function getBreadcrumb(): string
    {
        if ($this->isCreating) {
            return __('ecommerce.add_product');
        }

        return parent::getBreadcrumb();
    }

    protected function getSaveFormAction(): Action
    {
        $action = parent::getSaveFormAction()
            ->submit(null)
            ->requiresConfirmation()
            ->action('save');

        if ($this->isCreating) {
            return $action
                ->label(__('ecommerce.confirm_create_product_submit'))
                ->modalHeading(__('ecommerce.confirm_create_product_heading'))
                ->modalDescription(__('ecommerce.confirm_create_product_description'))
                ->modalSubmitActionLabel(__('ecommerce.confirm_create_product_submit'));
        }

        return $action
            ->modalHeading(__('ecommerce.confirm_update_product_heading'))
            ->modalDescription(__('ecommerce.confirm_update_product_description'))
            ->modalSubmitActionLabel(__('ecommerce.confirm_update_product_submit'));
    }

    protected function afterSave(): void
    {
        $this->isCreating = false;
    }

    protected function isUnfinishedCreateDraft(): bool
    {
        /** @var Product $record */
        $record = $this->getRecord();

        return $record->status === ProductStatus::Draft
            && str_starts_with((string) $record->sku, 'DRAFT-');
    }
}
