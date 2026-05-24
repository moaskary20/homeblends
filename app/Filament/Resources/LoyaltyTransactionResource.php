<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoyaltyTransactionResource\Pages;
use App\Models\LoyaltyTransaction;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LoyaltyTransactionResource extends Resource
{
    protected static ?string $model = LoyaltyTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('ecommerce.loyalty_program');
    }

    public static function getNavigationLabel(): string
    {
        return __('ecommerce.loyalty_transactions');
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user && ($user->is_admin || $user->can('loyalty.manage'));
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('ecommerce.customer'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.loyalty_points')
                    ->label(__('ecommerce.loyalty_balance'))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('user.store_credit')
                    ->label(__('ecommerce.store_credit'))
                    ->money('EGP', locale: 'ar')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('points')
                    ->label(__('ecommerce.loyalty_points'))
                    ->color(fn (int $state): string => $state >= 0 ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('type')
                    ->label(__('ecommerce.status'))
                    ->formatStateUsing(fn (LoyaltyTransaction $record): string => $record->typeLabel())
                    ->badge(),
                Tables\Columns\TextColumn::make('description')
                    ->label(__('ecommerce.description'))
                    ->limit(40),
                Tables\Columns\TextColumn::make('order.order_number')
                    ->label(__('ecommerce.order_number'))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('expires_at')
                    ->label(__('ecommerce.expires_at'))
                    ->dateTime('d/m/Y')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('ecommerce.created_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('ecommerce.status'))
                    ->options([
                        'earn' => __('ecommerce.loyalty_type_earn'),
                        'redeem' => __('ecommerce.loyalty_type_redeem'),
                        'expire' => __('ecommerce.loyalty_type_expire'),
                        'adjust' => __('ecommerce.loyalty_type_adjust'),
                        'wallet' => __('ecommerce.loyalty_type_wallet'),
                    ]),
            ])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLoyaltyTransactions::route('/'),
        ];
    }
}
