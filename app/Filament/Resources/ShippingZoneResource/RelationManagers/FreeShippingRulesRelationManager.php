<?php

namespace App\Filament\Resources\ShippingZoneResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class FreeShippingRulesRelationManager extends RelationManager
{
    protected static string $relationship = 'freeShippingRules';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('ecommerce.free_shipping_rules');
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('min_order_amount')
                ->label(__('ecommerce.min_order_amount'))
                ->numeric()
                ->required()
                ->prefix('ج.م'),
            Forms\Components\Toggle::make('is_active')
                ->label(__('ecommerce.is_active'))
                ->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('min_order_amount')
                    ->label(__('ecommerce.min_order_amount'))
                    ->money('EGP', locale: 'ar'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('ecommerce.is_active'))
                    ->boolean(),
            ])
            ->headerActions([Tables\Actions\CreateAction::make()])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
