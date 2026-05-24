<?php

namespace App\Filament\Resources;

use App\Enums\AffiliateCommissionStatus;
use App\Filament\Resources\AffiliateCommissionResource\Pages;
use App\Models\AffiliateCommission;
use App\Services\Affiliate\AffiliateCommissionService;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AffiliateCommissionResource extends Resource
{
    protected static ?string $model = AffiliateCommission::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('ecommerce.affiliates');
    }

    public static function getNavigationLabel(): string
    {
        return __('ecommerce.affiliate_commissions');
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user && ($user->is_admin || $user->can('affiliates.manage'));
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('affiliate.display_name')->label(__('ecommerce.affiliate')),
                Tables\Columns\TextColumn::make('order.order_number')->label(__('ecommerce.order_number'))->searchable(),
                Tables\Columns\TextColumn::make('order_amount')->money('EGP', locale: 'ar'),
                Tables\Columns\TextColumn::make('commission_rate')->suffix('%'),
                Tables\Columns\TextColumn::make('commission_amount')->money('EGP', locale: 'ar'),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('created_at')->dateTime('d/m/Y'),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->visible(fn (AffiliateCommission $record) => $record->status === AffiliateCommissionStatus::Pending)
                    ->action(fn (AffiliateCommission $record) => app(AffiliateCommissionService::class)->approve($record)),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAffiliateCommissions::route('/'),
        ];
    }
}
