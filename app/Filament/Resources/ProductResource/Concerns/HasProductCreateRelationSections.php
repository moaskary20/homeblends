<?php

namespace App\Filament\Resources\ProductResource\Concerns;

use App\Models\FlashSale;
use App\Models\Product;
use Filament\Forms;

trait HasProductCreateRelationSections
{
    /**
     * @return array<int, Forms\Components\Component>
     */
    public static function createRelationSections(): array
    {
        return [
            Forms\Components\Section::make(__('ecommerce.gallery'))
                ->description(__('ecommerce.product_create_gallery_help'))
                ->schema([
                    Forms\Components\Repeater::make('gallery_images')
                        ->label(__('ecommerce.gallery'))
                        ->schema([
                            Forms\Components\FileUpload::make('path')
                                ->label(__('ecommerce.image'))
                                ->image()
                                ->directory('products/gallery')
                                ->required(),
                            Forms\Components\TextInput::make('alt')
                                ->label(__('ecommerce.image_alt')),
                            Forms\Components\TextInput::make('sort_order')
                                ->label(__('ecommerce.sort_order'))
                                ->numeric()
                                ->default(0),
                        ])
                        ->columns(3)
                        ->defaultItems(0)
                        ->collapsible()
                        ->reorderable()
                        ->addActionLabel(__('ecommerce.add_gallery_image'))
                        ->columnSpanFull(),
                ])
                ->collapsed(false)
                ->columnSpanFull()
                ->visibleOn('create'),

            Forms\Components\Section::make(__('ecommerce.variants'))
                ->description(__('ecommerce.product_create_variants_help'))
                ->schema([
                    Forms\Components\Repeater::make('product_variants')
                        ->label(__('ecommerce.variants'))
                        ->schema([
                            Forms\Components\TextInput::make('sku')
                                ->label(__('ecommerce.sku'))
                                ->required()
                                ->distinct()
                                ->unique(table: 'product_variants', column: 'sku'),
                            Forms\Components\TextInput::make('barcode')
                                ->label(__('ecommerce.barcode')),
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
                            Forms\Components\Toggle::make('is_default')
                                ->label(__('ecommerce.is_default_variant'))
                                ->default(false),
                        ])
                        ->columns(2)
                        ->defaultItems(0)
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['sku'] ?? null)
                        ->addActionLabel(__('ecommerce.add_variant'))
                        ->columnSpanFull(),
                ])
                ->collapsed(false)
                ->columnSpanFull()
                ->visibleOn('create'),

            Forms\Components\Section::make(__('ecommerce.flash_sales'))
                ->description(__('ecommerce.product_create_flash_help'))
                ->schema([
                    Forms\Components\Repeater::make('flash_sale_entries')
                        ->label(__('ecommerce.flash_sales'))
                        ->schema([
                            Forms\Components\Select::make('flash_sale_id')
                                ->label(__('ecommerce.flash_sale'))
                                ->options(fn (): array => FlashSale::query()
                                    ->orderByDesc('starts_at')
                                    ->get()
                                    ->mapWithKeys(fn (FlashSale $sale) => [
                                        $sale->id => $sale->name.' ('.$sale->statusLabel().')',
                                    ])
                                    ->all())
                                ->searchable()
                                ->required()
                                ->distinct()
                                ->native(false),
                            Forms\Components\TextInput::make('sale_price')
                                ->label(__('ecommerce.flash_price'))
                                ->numeric()
                                ->required()
                                ->minValue(0.01)
                                ->prefix('ج.م')
                                ->helperText(__('ecommerce.flash_price_hint')),
                            Forms\Components\TextInput::make('stock_limit')
                                ->label(__('ecommerce.flash_quantity_limit'))
                                ->numeric()
                                ->minValue(1)
                                ->helperText(__('ecommerce.flash_quantity_limit_hint')),
                            Forms\Components\TextInput::make('sort_order')
                                ->label(__('ecommerce.sort_order'))
                                ->numeric()
                                ->default(0),
                        ])
                        ->columns(2)
                        ->defaultItems(0)
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => isset($state['flash_sale_id'])
                            ? FlashSale::find($state['flash_sale_id'])?->name
                            : null)
                        ->addActionLabel(__('ecommerce.flash_add_to_sale'))
                        ->columnSpanFull(),
                ])
                ->collapsed(false)
                ->columnSpanFull()
                ->visibleOn('create'),

            Forms\Components\Section::make(__('ecommerce.related_products'))
                ->description(__('ecommerce.product_create_related_help'))
                ->schema([
                    Forms\Components\Select::make('related_product_ids')
                        ->label(__('ecommerce.related_products'))
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->options(fn (): array => Product::query()
                            ->orderBy('name')
                            ->get(['id', 'name', 'sku'])
                            ->mapWithKeys(fn (Product $product) => [
                                $product->id => $product->name.' ('.$product->sku.')',
                            ])
                            ->all())
                        ->columnSpanFull(),
                ])
                ->collapsed(false)
                ->columnSpanFull()
                ->visibleOn('create'),
        ];
    }
}
