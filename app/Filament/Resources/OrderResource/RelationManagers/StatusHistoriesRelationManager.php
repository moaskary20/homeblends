<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Enums\OrderStatus;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class StatusHistoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'statusHistory';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('ecommerce.order_tracking');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('status')
                    ->label(__('ecommerce.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state) => OrderStatus::tryFrom($state)?->label() ?? $state),
                Tables\Columns\TextColumn::make('comment')
                    ->label(__('ecommerce.notes'))
                    ->wrap(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('ecommerce.created_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
