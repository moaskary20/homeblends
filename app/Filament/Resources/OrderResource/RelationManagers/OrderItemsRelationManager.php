<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class OrderItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('ecommerce.order_items');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product_name')->label(__('ecommerce.product')),
                Tables\Columns\TextColumn::make('sku')->label(__('ecommerce.sku')),
                Tables\Columns\TextColumn::make('quantity')->label(__('ecommerce.quantity')),
                Tables\Columns\TextColumn::make('unit_price')->money('EGP', locale: 'ar')->label(__('ecommerce.regular_price')),
                Tables\Columns\TextColumn::make('total')->money('EGP', locale: 'ar')->label(__('ecommerce.total')),
            ]);
    }
}
