<?php

namespace App\Filament\Resources\ProductBundleResource\RelationManagers;

use App\Models\ProductVariant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('ecommerce.bundle_items');
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('product_id')
                ->label(__('ecommerce.product'))
                ->relationship('product', 'name', fn ($query) => $query->published())
                ->searchable()
                ->preload()
                ->required()
                ->live(),
            Forms\Components\Select::make('product_variant_id')
                ->label(__('ecommerce.variants'))
                ->options(fn (Get $get): array => ProductVariant::query()
                    ->where('product_id', $get('product_id'))
                    ->pluck('sku', 'id')
                    ->all())
                ->nullable(),
            Forms\Components\TextInput::make('quantity')
                ->label(__('ecommerce.quantity'))
                ->numeric()
                ->minValue(1)
                ->default(1)
                ->required(),
            Forms\Components\TextInput::make('sort_order')
                ->label(__('ecommerce.sort_order'))
                ->numeric()
                ->default(0),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name')->label(__('ecommerce.product')),
                Tables\Columns\TextColumn::make('variant.sku')->label(__('ecommerce.variants'))->placeholder('—'),
                Tables\Columns\TextColumn::make('quantity')->label(__('ecommerce.quantity')),
                Tables\Columns\TextColumn::make('line_total')
                    ->label(__('ecommerce.total'))
                    ->state(fn ($record) => $record->lineRegularTotal())
                    ->money('EGP', locale: 'ar'),
            ])
            ->headerActions([Tables\Actions\CreateAction::make()])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('sort_order');
    }
}
