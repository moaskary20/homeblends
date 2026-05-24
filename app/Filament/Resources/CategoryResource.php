<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = null;

    protected static ?string $navigationLabel = null;

    protected static ?string $modelLabel = null;

    protected static ?string $pluralModelLabel = null;

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('ecommerce.catalog');
    }

    public static function getNavigationLabel(): string
    {
        return __('ecommerce.categories');
    }

    public static function getModelLabel(): string
    {
        return __('ecommerce.category');
    }

    public static function getPluralModelLabel(): string
    {
        return __('ecommerce.categories');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('parent_id')
                ->label(__('ecommerce.parent_category'))
                ->relationship('parent', 'name')
                ->searchable()
                ->preload(),
            Forms\Components\TextInput::make('name')
                ->label(__('ecommerce.name'))
                ->required()
                ->live(onBlur: true)
                ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Category::slugify($state ?? ''))),
            Forms\Components\TextInput::make('slug')
                ->label(__('ecommerce.slug'))
                ->required()
                ->unique(ignoreRecord: true),
            Forms\Components\FileUpload::make('image')
                ->label(__('ecommerce.image'))
                ->image()
                ->directory('categories'),
            Forms\Components\Textarea::make('description')
                ->label(__('ecommerce.description'))
                ->columnSpanFull(),
            Forms\Components\TextInput::make('meta_title')->label(__('ecommerce.meta_title')),
            Forms\Components\Textarea::make('meta_description')->label(__('ecommerce.meta_description')),
            Forms\Components\Toggle::make('is_active')
                ->label(__('ecommerce.is_active'))
                ->default(true),
            Forms\Components\TextInput::make('sort_order')
                ->label(__('ecommerce.sort_order'))
                ->numeric()
                ->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')->label(__('ecommerce.image')),
                Tables\Columns\TextColumn::make('name')->label(__('ecommerce.name'))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('parent.name')->label(__('ecommerce.parent')),
                Tables\Columns\IconColumn::make('is_active')->label(__('ecommerce.is_active'))->boolean(),
                Tables\Columns\TextColumn::make('products_count')->label(__('ecommerce.products'))->counts('products'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label(__('ecommerce.is_active')),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
