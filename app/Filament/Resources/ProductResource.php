<?php

namespace App\Filament\Resources;

use App\Enums\ProductStatus;
use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers\FlashSalesRelationManager;
use App\Filament\Resources\ProductResource\RelationManagers\ImagesRelationManager;
use App\Filament\Resources\ProductResource\RelationManagers\RelatedProductsRelationManager;
use App\Filament\Resources\ProductResource\RelationManagers\VariantsRelationManager;
use App\Models\Product;
use Filament\Forms;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('ecommerce.catalog');
    }

    public static function getNavigationLabel(): string
    {
        return __('ecommerce.products');
    }

    public static function getModelLabel(): string
    {
        return __('ecommerce.product');
    }

    public static function getPluralModelLabel(): string
    {
        return __('ecommerce.products');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['activeFlashSaleEntry.flashSale']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make(__('ecommerce.basic_info'))->schema([
                    Forms\Components\Select::make('category_id')
                        ->label(__('ecommerce.categories'))
                        ->relationship('category', 'name')
                        ->required()
                        ->searchable(),
                    Forms\Components\TextInput::make('name')
                        ->label(__('ecommerce.name'))
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Product::slugify($state ?? ''))),
                    Forms\Components\TextInput::make('slug')
                        ->label(__('ecommerce.slug'))
                        ->required()
                        ->unique(ignoreRecord: true),
                    Forms\Components\TextInput::make('sku')
                        ->label(__('ecommerce.sku'))
                        ->required()
                        ->unique(ignoreRecord: true),
                    Forms\Components\TextInput::make('barcode')->label(__('ecommerce.barcode')),
                    Forms\Components\Select::make('status')
                        ->label(__('ecommerce.status'))
                        ->options(collect(ProductStatus::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()]))
                        ->required(),
                    Forms\Components\Toggle::make('is_featured')->label(__('ecommerce.is_featured')),
                ])->columns(2),
                Forms\Components\Section::make(__('ecommerce.pricing_stock'))->schema([
                    Forms\Components\TextInput::make('regular_price')
                        ->label(__('ecommerce.regular_price'))
                        ->numeric()
                        ->required()
                        ->prefix('ج.م'),
                    Forms\Components\Fieldset::make(__('ecommerce.timed_discount'))
                        ->schema([
                            Forms\Components\TextInput::make('discount_price')
                                ->label(__('ecommerce.discount_price'))
                                ->numeric()
                                ->minValue(0)
                                ->prefix('ج.م')
                                ->helperText(__('ecommerce.discount_price_hint')),
                            Forms\Components\DateTimePicker::make('discount_starts_at')
                                ->label(__('ecommerce.discount_starts_at'))
                                ->seconds(false),
                            Forms\Components\DateTimePicker::make('discount_ends_at')
                                ->label(__('ecommerce.discount_ends_at'))
                                ->seconds(false)
                                ->after('discount_starts_at'),
                        ])
                        ->columns(3)
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('cost_price')
                        ->label(__('ecommerce.cost_price'))
                        ->numeric()
                        ->prefix('ج.م'),
                    Forms\Components\TextInput::make('stock_quantity')
                        ->label(__('ecommerce.stock_quantity'))
                        ->numeric()
                        ->required(),
                    Forms\Components\TextInput::make('low_stock_threshold')
                        ->label(__('ecommerce.low_stock_threshold'))
                        ->numeric()
                        ->default(5),
                ])->columns(2),
                Forms\Components\Section::make(__('ecommerce.content'))->schema([
                    Forms\Components\Textarea::make('short_description')
                        ->label(__('ecommerce.short_description')),
                    Forms\Components\RichEditor::make('full_description')
                        ->label(__('ecommerce.full_description'))
                        ->columnSpanFull(),
                    Forms\Components\FileUpload::make('main_image')
                        ->label(__('ecommerce.main_image'))
                        ->image()
                        ->directory('products'),
                ]),
            ])->columnSpan(['lg' => 2]),
            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make(__('ecommerce.seo'))->schema([
                    Forms\Components\TextInput::make('meta_title')->label(__('ecommerce.meta_title')),
                    Forms\Components\Textarea::make('meta_description')->label(__('ecommerce.meta_description')),
                ]),
                Forms\Components\Section::make(__('ecommerce.dimensions'))->schema([
                    Forms\Components\TextInput::make('weight')
                        ->label(__('ecommerce.weight'))
                        ->numeric(),
                    Forms\Components\TextInput::make('dimensions')->label(__('ecommerce.dimensions')),
                ]),
            ])->columnSpan(['lg' => 1]),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('main_image')->label(__('ecommerce.image')),
                Tables\Columns\TextColumn::make('name')->label(__('ecommerce.name'))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('sku')->label(__('ecommerce.sku'))->searchable(),
                Tables\Columns\TextColumn::make('category.name')->label(__('ecommerce.categories')),
                Tables\Columns\TextColumn::make('regular_price')->label(__('ecommerce.regular_price'))->money('EGP', locale: 'ar'),
                Tables\Columns\TextColumn::make('discount_price')
                    ->label(__('ecommerce.discount_price'))
                    ->money('EGP', locale: 'ar')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('discount_status')
                    ->label(__('ecommerce.discount_status'))
                    ->badge()
                    ->state(fn (Product $record): string => $record->discountStatusLabel())
                    ->color(fn (Product $record): string => match (true) {
                        $record->hasActiveTimedDiscount() => 'success',
                        $record->discount_price && $record->discount_starts_at && now()->lt($record->discount_starts_at) => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('stock_quantity')->label(__('ecommerce.stock_quantity'))->sortable(),
                Tables\Columns\IconColumn::make('is_featured')->label(__('ecommerce.is_featured'))->boolean(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('ecommerce.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof ProductStatus ? $state->label() : $state),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('ecommerce.status'))
                    ->options(collect(ProductStatus::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])),
                Tables\Filters\SelectFilter::make('category_id')
                    ->label(__('ecommerce.categories'))
                    ->relationship('category', 'name'),
                Tables\Filters\TernaryFilter::make('is_featured')->label(__('ecommerce.is_featured')),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getRelations(): array
    {
        return [
            ImagesRelationManager::class,
            VariantsRelationManager::class,
            FlashSalesRelationManager::class,
            RelatedProductsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
