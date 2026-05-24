<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'images';

    protected static ?string $title = null;

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('ecommerce.gallery');
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\FileUpload::make('path')
                ->label(__('ecommerce.image'))
                ->image()
                ->directory('products/gallery')
                ->required(),
            Forms\Components\TextInput::make('alt')->label(__('ecommerce.image_alt')),
            Forms\Components\TextInput::make('sort_order')
                ->label(__('ecommerce.sort_order'))
                ->numeric()
                ->default(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('path')->label(__('ecommerce.image')),
                Tables\Columns\TextColumn::make('alt')->label(__('ecommerce.image_alt')),
                Tables\Columns\TextColumn::make('sort_order')->label(__('ecommerce.sort_order')),
            ])
            ->headerActions([Tables\Actions\CreateAction::make()])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->reorderable('sort_order');
    }
}
