<?php

namespace App\Filament\Resources\FlashSaleResource\Concerns;

use App\Models\Product;
use App\Models\ProductVariant;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Set;

trait HasFlashSaleProductForm
{
    public static function flashSaleProductsRepeater(): Forms\Components\Repeater
    {
        return Forms\Components\Repeater::make('flash_products')
            ->label(__('ecommerce.flash_sale_products'))
            ->schema([
                Forms\Components\Hidden::make('id'),
                Forms\Components\Select::make('product_id')
                    ->label(__('ecommerce.product'))
                    ->options(fn (): array => Product::query()
                        ->published()
                        ->orderBy('name')
                        ->get()
                        ->mapWithKeys(fn (Product $product) => [
                            $product->id => static::productOptionLabel($product),
                        ])
                        ->all())
                    ->searchable()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (?int $state, Set $set): void {
                        if (! $state) {
                            return;
                        }

                        $product = Product::find($state);
                        if (! $product) {
                            return;
                        }

                        $suggested = $product->hasActiveTimedDiscount()
                            ? (float) $product->discount_price
                            : round((float) $product->regular_price * 0.85, 2);

                        $set('sale_price', $suggested);
                        $set('product_variant_id', null);
                    }),
                Forms\Components\Select::make('product_variant_id')
                    ->label(__('ecommerce.variants'))
                    ->options(fn (Get $get): array => ProductVariant::query()
                        ->where('product_id', $get('product_id'))
                        ->get()
                        ->mapWithKeys(fn (ProductVariant $variant) => [
                            $variant->id => $variant->sku.' — '.number_format((float) $variant->price, 2).' ج.م',
                        ])
                        ->all())
                    ->visible(fn (Get $get): bool => filled($get('product_id'))
                        && ProductVariant::where('product_id', $get('product_id'))->exists())
                    ->nullable()
                    ->live()
                    ->afterStateUpdated(function (?int $state, Set $set): void {
                        if (! $state) {
                            return;
                        }

                        $variant = ProductVariant::find($state);
                        if ($variant) {
                            $set('sale_price', round((float) $variant->price * 0.85, 2));
                        }
                    }),
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
            ->collapsible()
            ->itemLabel(fn (array $state): ?string => isset($state['product_id'])
                ? Product::find($state['product_id'])?->name
                : null)
            ->addActionLabel(__('ecommerce.flash_add_product'))
            ->defaultItems(0)
            ->columnSpanFull();
    }

    protected static function productOptionLabel(Product $product): string
    {
        return sprintf(
            '%s (%s) — %s ج.م',
            $product->name,
            $product->sku,
            number_format((float) $product->regular_price, 2)
        );
    }
}
