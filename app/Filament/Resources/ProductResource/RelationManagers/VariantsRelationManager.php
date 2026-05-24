<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('ecommerce.variants');
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('sku')
                ->label(__('ecommerce.sku'))
                ->required()
                ->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('barcode')->label(__('ecommerce.barcode')),
            Forms\Components\TextInput::make('price')
                ->label(__('ecommerce.variant_price'))
                ->numeric()
                ->required()
                ->prefix('ج.م'),
            Forms\Components\TextInput::make('compare_price')
                ->label(__('ecommerce.compare_price'))
                ->numeric()
                ->prefix('ج.م'),
            Forms\Components\TextInput::make('stock_quantity')
                ->label(__('ecommerce.stock_quantity'))
                ->numeric()
                ->required()
                ->default(0),
            Forms\Components\FileUpload::make('image')
                ->label(__('ecommerce.image'))
                ->image()
                ->directory('products/variants'),
            Forms\Components\Toggle::make('is_default')->label(__('ecommerce.is_default_variant')),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')->label(__('ecommerce.image')),
                Tables\Columns\TextColumn::make('sku')->label(__('ecommerce.sku')),
                Tables\Columns\TextColumn::make('price')->label(__('ecommerce.variant_price'))->money('EGP', locale: 'ar'),
                Tables\Columns\TextColumn::make('stock_quantity')->label(__('ecommerce.stock_quantity')),
                Tables\Columns\IconColumn::make('is_default')->label(__('ecommerce.is_default_variant'))->boolean(),
            ])
            ->headerActions([Tables\Actions\CreateAction::make()])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
