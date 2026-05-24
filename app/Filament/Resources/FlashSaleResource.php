<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FlashSaleResource\Concerns\HasFlashSaleProductForm;
use App\Filament\Resources\FlashSaleResource\Pages;
use App\Filament\Resources\FlashSaleResource\RelationManagers\ProductsRelationManager;
use App\Models\FlashSale;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FlashSaleResource extends Resource
{
    use HasFlashSaleProductForm;

    protected static ?string $model = FlashSale::class;

    protected static ?string $navigationIcon = 'heroicon-o-bolt';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('ecommerce.sales');
    }

    public static function getNavigationLabel(): string
    {
        return __('ecommerce.flash_sales');
    }

    public static function getModelLabel(): string
    {
        return __('ecommerce.flash_sale');
    }

    public static function getPluralModelLabel(): string
    {
        return __('ecommerce.flash_sales');
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user && ($user->is_admin || $user->can('flash_sales.manage'));
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(__('ecommerce.flash_sale_details'))
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label(__('ecommerce.name'))
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, callable $set) => $set('slug', FlashSale::slugify($state ?? ''))),
                    Forms\Components\TextInput::make('slug')
                        ->label(__('ecommerce.slug'))
                        ->required()
                        ->unique(ignoreRecord: true),
                    Forms\Components\Textarea::make('description')
                        ->label(__('ecommerce.description'))
                        ->columnSpanFull(),
                    Forms\Components\DateTimePicker::make('starts_at')
                        ->label(__('ecommerce.starts_at'))
                        ->required(),
                    Forms\Components\DateTimePicker::make('ends_at')
                        ->label(__('ecommerce.ends_at'))
                        ->required()
                        ->after('starts_at'),
                    Forms\Components\Toggle::make('is_active')
                        ->label(__('ecommerce.is_active'))
                        ->default(true),
                    Forms\Components\TextInput::make('sort_order')
                        ->label(__('ecommerce.sort_order'))
                        ->numeric()
                        ->default(0),
                    Forms\Components\FileUpload::make('banner_image')
                        ->label(__('ecommerce.banner_image'))
                        ->image()
                        ->directory('flash-sales'),
                ])
                ->columns(2),
            Forms\Components\Section::make(__('ecommerce.flash_sale_products'))
                ->description(__('ecommerce.flash_sale_products_help'))
                ->schema([
                    static::flashSaleProductsRepeater(),
                ]),
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
                Tables\Columns\TextColumn::make('starts_at')
                    ->label(__('ecommerce.starts_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('ends_at')
                    ->label(__('ecommerce.ends_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('products_count')
                    ->label(__('ecommerce.products_count'))
                    ->counts('products'),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('ecommerce.status'))
                    ->badge()
                    ->state(fn (FlashSale $record): string => $record->statusLabel())
                    ->color(fn (FlashSale $record): string => match (true) {
                        $record->isRunning() => 'success',
                        $record->isUpcoming() => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('ecommerce.is_active'))
                    ->boolean(),
            ])
            ->defaultSort('starts_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label(__('ecommerce.is_active')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ProductsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFlashSales::route('/'),
            'create' => Pages\CreateFlashSale::route('/create'),
            'edit' => Pages\EditFlashSale::route('/{record}/edit'),
        ];
    }
}
