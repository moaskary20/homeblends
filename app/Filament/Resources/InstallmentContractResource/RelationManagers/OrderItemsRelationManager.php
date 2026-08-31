<?php

namespace App\Filament\Resources\InstallmentContractResource\RelationManagers;

use App\Enums\OrderItemFulfillmentStatus;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class OrderItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'orderItems';

    protected static bool $isLazy = false;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('ecommerce.installment_contract_products');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product_name')
            ->columns([
                Tables\Columns\TextColumn::make('product_name')->label(__('ecommerce.product')),
                Tables\Columns\TextColumn::make('sku')->label(__('ecommerce.sku')),
                Tables\Columns\TextColumn::make('quantity')->label(__('ecommerce.quantity')),
                Tables\Columns\SelectColumn::make('fulfillment_status')
                    ->label(__('ecommerce.item_fulfillment_status'))
                    ->options(OrderItemFulfillmentStatus::options())
                    ->selectablePlaceholder(false)
                    ->afterStateUpdated(function (): void {
                        Notification::make()
                            ->title(__('ecommerce.item_status_updated'))
                            ->success()
                            ->send();
                    }),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
