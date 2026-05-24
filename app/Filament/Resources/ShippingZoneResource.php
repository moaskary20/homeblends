<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShippingZoneResource\Pages;
use App\Filament\Resources\ShippingZoneResource\RelationManagers\FreeShippingRulesRelationManager;
use App\Filament\Resources\ShippingZoneResource\RelationManagers\ShippingRatesRelationManager;
use App\Models\ShippingZone;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ShippingZoneResource extends Resource
{
    protected static ?string $model = ShippingZone::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('ecommerce.shipping');
    }

    public static function getNavigationLabel(): string
    {
        return __('ecommerce.shipping_zones');
    }

    public static function getModelLabel(): string
    {
        return __('ecommerce.shipping_zone');
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user && ($user->is_admin || $user->can('shipping.manage'));
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label(__('ecommerce.name'))
                ->required()
                ->maxLength(255),
            Forms\Components\TagsInput::make('countries')
                ->label(__('ecommerce.zone_countries'))
                ->helperText(__('ecommerce.zone_countries_help'))
                ->placeholder('EG')
                ->suggestions(['EG', 'SA', 'AE', 'KW', 'QA', 'BH', 'OM', 'JO', 'LB']),
            Forms\Components\TagsInput::make('regions')
                ->label(__('ecommerce.zone_regions'))
                ->helperText(__('ecommerce.zone_regions_help')),
            Forms\Components\Toggle::make('is_active')
                ->label(__('ecommerce.is_active'))
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('ecommerce.name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('countries')
                    ->label(__('ecommerce.zone_countries'))
                    ->badge()
                    ->placeholder(__('ecommerce.all_countries')),
                Tables\Columns\TextColumn::make('rates_count')
                    ->label(__('ecommerce.shipping_rates'))
                    ->counts('rates'),
                Tables\Columns\TextColumn::make('free_shipping_rules_count')
                    ->label(__('ecommerce.free_shipping_rules'))
                    ->counts('freeShippingRules'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('ecommerce.is_active'))
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label(__('ecommerce.is_active')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ShippingRatesRelationManager::class,
            FreeShippingRulesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShippingZones::route('/'),
            'create' => Pages\CreateShippingZone::route('/create'),
            'edit' => Pages\EditShippingZone::route('/{record}/edit'),
        ];
    }
}
