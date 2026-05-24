<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class RelatedProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'relatedProducts';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('ecommerce.related_products');
    }

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')->label(__('ecommerce.name')),
                Tables\Columns\TextColumn::make('sku')->label(__('ecommerce.sku')),
                Tables\Columns\TextColumn::make('regular_price')
                    ->label(__('ecommerce.regular_price'))
                    ->money('EGP', locale: 'ar'),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(fn ($query) => $query
                        ->where('id', '!=', $this->getOwnerRecord()->id)
                        ->published()),
            ])
            ->actions([
                Tables\Actions\DetachAction::make(),
            ]);
    }
}
