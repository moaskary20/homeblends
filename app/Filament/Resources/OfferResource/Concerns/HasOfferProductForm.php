<?php

namespace App\Filament\Resources\OfferResource\Concerns;

use App\Models\Product;
use App\Models\ProductVariant;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Set;

trait HasOfferProductForm
{
    public static function offerProductsRepeater(): Forms\Components\Repeater
    {
        return Forms\Components\Repeater::make('offer_products')
            ->label(__('ecommerce.offer_products'))
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
                    ->distinct()
                    ->live()
                    ->afterStateUpdated(function (?int $state, Set $set): void {
                        if (! $state) {
                            return;
                        }

                        $product = Product::find($state);
                        if (! $product) {
                            return;
                        }

                        $set('offer_price', (float) $product->baseSellingPrice());
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
                            $set('offer_price', (float) $variant->price);
                        }
                    }),
                Forms\Components\TextInput::make('offer_price')
                    ->label(__('ecommerce.offer_price'))
                    ->numeric()
                    ->required()
                    ->minValue(0.01)
                    ->prefix('ج.م')
                    ->helperText(__('ecommerce.offer_price_hint')),
                Forms\Components\TextInput::make('stock_limit')
                    ->label(__('ecommerce.offer_quantity_limit'))
                    ->numeric()
                    ->minValue(1)
                    ->helperText(__('ecommerce.offer_quantity_limit_hint')),
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
            ->addActionLabel(__('ecommerce.offer_add_product'))
            ->defaultItems(0)
            ->reorderable()
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
