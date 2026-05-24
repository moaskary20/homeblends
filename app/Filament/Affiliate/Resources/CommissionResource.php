<?php

namespace App\Filament\Affiliate\Resources;

use App\Filament\Affiliate\Resources\CommissionResource\Pages;
use App\Models\AffiliateCommission;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CommissionResource extends Resource
{
    protected static ?string $model = AffiliateCommission::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('ecommerce.affiliate_commissions');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('affiliate_id', auth()->user()->affiliate?->id);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order.order_number')->label(__('ecommerce.order_number')),
                Tables\Columns\TextColumn::make('order_amount')->money('EGP', locale: 'ar'),
                Tables\Columns\TextColumn::make('commission_amount')->money('EGP', locale: 'ar'),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('created_at')->dateTime('d/m/Y'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCommissions::route('/'),
        ];
    }
}
