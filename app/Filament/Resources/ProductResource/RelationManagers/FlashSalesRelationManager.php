<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class FlashSalesRelationManager extends RelationManager
{
    protected static string $relationship = 'flashSaleEntries';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('ecommerce.flash_sales');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('flashSale.name')->label(__('ecommerce.flash_sale')),
                Tables\Columns\TextColumn::make('sale_price')
                    ->label(__('ecommerce.flash_price'))
                    ->money('EGP', locale: 'ar'),
                Tables\Columns\TextColumn::make('flashSale.starts_at')
                    ->label(__('ecommerce.starts_at'))
                    ->dateTime('d/m/Y H:i'),
                Tables\Columns\TextColumn::make('flashSale.ends_at')
                    ->label(__('ecommerce.ends_at'))
                    ->dateTime('d/m/Y H:i'),
                Tables\Columns\TextColumn::make('flashSale.status')
                    ->label(__('ecommerce.status'))
                    ->badge()
                    ->state(fn ($record) => $record->flashSale?->statusLabel()),
                Tables\Columns\TextColumn::make('quantity_sold')
                    ->label(__('ecommerce.quantity_sold')),
                Tables\Columns\TextColumn::make('stock_limit')
                    ->label(__('ecommerce.flash_quantity_limit'))
                    ->placeholder('∞'),
            ])
            ->actions([
                Tables\Actions\Action::make('edit_sale')
                    ->label(__('ecommerce.edit'))
                    ->url(fn ($record) => \App\Filament\Resources\FlashSaleResource::getUrl('edit', ['record' => $record->flash_sale_id]))
                    ->icon('heroicon-o-pencil'),
            ]);
    }
}
