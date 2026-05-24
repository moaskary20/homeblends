<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductBundleResource\Pages;
use App\Filament\Resources\ProductBundleResource\RelationManagers\ItemsRelationManager;
use App\Models\ProductBundle;
use App\Services\Bundle\BundleService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductBundleResource extends Resource
{
    protected static ?string $model = ProductBundle::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return __('ecommerce.catalog');
    }

    public static function getNavigationLabel(): string
    {
        return __('ecommerce.product_bundles');
    }

    public static function getModelLabel(): string
    {
        return __('ecommerce.product_bundle');
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user && ($user->is_admin || $user->can('bundles.manage'));
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(__('ecommerce.bundle_details'))
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label(__('ecommerce.name'))
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, callable $set) => $set('slug', ProductBundle::slugify($state ?? ''))),
                    Forms\Components\TextInput::make('slug')
                        ->label(__('ecommerce.slug'))
                        ->required()
                        ->unique(ignoreRecord: true),
                    Forms\Components\Textarea::make('short_description')
                        ->label(__('ecommerce.short_description'))
                        ->columnSpanFull(),
                    Forms\Components\RichEditor::make('description')
                        ->label(__('ecommerce.description'))
                        ->columnSpanFull(),
                    Forms\Components\FileUpload::make('main_image')
                        ->label(__('ecommerce.main_image'))
                        ->image()
                        ->directory('bundles'),
                    Forms\Components\TextInput::make('bundle_price')
                        ->label(__('ecommerce.bundle_price'))
                        ->numeric()
                        ->required()
                        ->prefix('ج.م')
                        ->live(),
                    Forms\Components\Placeholder::make('pricing_hint')
                        ->label(__('ecommerce.bundle_savings'))
                        ->content(function (?ProductBundle $record, Get $get): string {
                            if (! $record?->exists) {
                                return '—';
                            }

                            $record->bundle_price = $get('bundle_price') ?? $record->bundle_price;
                            $service = app(BundleService::class);
                            $regular = $service->calculateRegularTotal($record);
                            $savings = $service->calculateSavings($record);

                            return number_format($regular, 2).' ج.م → '.number_format((float) $record->bundle_price, 2)
                                .' ج.م ('.__('ecommerce.you_save').' '.number_format($savings, 2).' ج.م — '
                                .$service->savingsPercent($record).'%)';
                        }),
                    Forms\Components\DateTimePicker::make('starts_at')
                        ->label(__('ecommerce.starts_at'))
                        ->seconds(false),
                    Forms\Components\DateTimePicker::make('ends_at')
                        ->label(__('ecommerce.ends_at'))
                        ->seconds(false)
                        ->after('starts_at'),
                    Forms\Components\Toggle::make('is_active')
                        ->label(__('ecommerce.is_active'))
                        ->default(true),
                    Forms\Components\TextInput::make('sort_order')
                        ->label(__('ecommerce.sort_order'))
                        ->numeric()
                        ->default(0),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('main_image')->label(__('ecommerce.image')),
                Tables\Columns\TextColumn::make('name')->label(__('ecommerce.name'))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('bundle_price')
                    ->label(__('ecommerce.bundle_price'))
                    ->money('EGP', locale: 'ar'),
                Tables\Columns\TextColumn::make('regular_total')
                    ->label(__('ecommerce.regular_total'))
                    ->state(fn (ProductBundle $record) => app(BundleService::class)->calculateRegularTotal($record))
                    ->money('EGP', locale: 'ar'),
                Tables\Columns\TextColumn::make('items_count')
                    ->label(__('ecommerce.products_count'))
                    ->counts('items'),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('ecommerce.status'))
                    ->badge()
                    ->state(fn (ProductBundle $record) => $record->statusLabel())
                    ->color(fn (ProductBundle $record) => $record->isAvailable() ? 'success' : 'gray'),
                Tables\Columns\IconColumn::make('is_active')->label(__('ecommerce.is_active'))->boolean(),
            ])
            ->defaultSort('sort_order')
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getRelations(): array
    {
        return [ItemsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductBundles::route('/'),
            'create' => Pages\CreateProductBundle::route('/create'),
            'edit' => Pages\EditProductBundle::route('/{record}/edit'),
        ];
    }
}
