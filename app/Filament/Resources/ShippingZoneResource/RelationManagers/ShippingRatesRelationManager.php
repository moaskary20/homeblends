<?php

namespace App\Filament\Resources\ShippingZoneResource\RelationManagers;

use App\Enums\ShippingRateType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ShippingRatesRelationManager extends RelationManager
{
    protected static string $relationship = 'rates';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('ecommerce.shipping_rates');
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label(__('ecommerce.name'))
                ->required()
                ->maxLength(255),
            Forms\Components\Select::make('type')
                ->label(__('ecommerce.shipping_rate_type'))
                ->options(collect(ShippingRateType::cases())->mapWithKeys(
                    fn (ShippingRateType $type) => [$type->value => $type->label()]
                ))
                ->required()
                ->live(),
            Forms\Components\TextInput::make('rate')
                ->label(__('ecommerce.shipping_rate_amount'))
                ->numeric()
                ->required()
                ->prefix('ج.م'),
            Forms\Components\TextInput::make('min_value')
                ->label(__('ecommerce.shipping_min_value'))
                ->numeric()
                ->visible(fn (Get $get): bool => in_array($get('type'), ['weight', 'price'], true)),
            Forms\Components\TextInput::make('max_value')
                ->label(__('ecommerce.shipping_max_value'))
                ->numeric()
                ->visible(fn (Get $get): bool => in_array($get('type'), ['weight', 'price'], true)),
            Forms\Components\TextInput::make('estimated_days')
                ->label(__('ecommerce.estimated_days'))
                ->numeric()
                ->minValue(1),
            Forms\Components\Toggle::make('is_active')
                ->label(__('ecommerce.is_active'))
                ->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label(__('ecommerce.name')),
                Tables\Columns\TextColumn::make('type')
                    ->label(__('ecommerce.shipping_rate_type'))
                    ->formatStateUsing(fn (string $state): string => ShippingRateType::tryFrom($state)?->label() ?? $state),
                Tables\Columns\TextColumn::make('rate')
                    ->label(__('ecommerce.shipping_rate_amount'))
                    ->money('EGP', locale: 'ar'),
                Tables\Columns\TextColumn::make('min_value')
                    ->label(__('ecommerce.shipping_min_value'))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('max_value')
                    ->label(__('ecommerce.shipping_max_value'))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('estimated_days')
                    ->label(__('ecommerce.estimated_days'))
                    ->suffix(' '.__('ecommerce.days')),
                Tables\Columns\IconColumn::make('is_active')->label(__('ecommerce.is_active'))->boolean(),
            ])
            ->headerActions([Tables\Actions\CreateAction::make()])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
