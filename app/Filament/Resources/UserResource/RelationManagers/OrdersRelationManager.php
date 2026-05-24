<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    protected static ?string $title = null;

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('ecommerce.orders');
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('order_number')
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label(__('ecommerce.order_number'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('ecommerce.status'))
                    ->badge(),
                Tables\Columns\TextColumn::make('total')
                    ->label(__('ecommerce.total'))
                    ->money('EGP', locale: 'ar'),
                Tables\Columns\TextColumn::make('payment_status')
                    ->label(__('ecommerce.payment_status')),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('ecommerce.created_at'))
                    ->dateTime('d/m/Y H:i'),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label(__('ecommerce.view_order'))
                    ->url(fn ($record) => \App\Filament\Resources\OrderResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}
