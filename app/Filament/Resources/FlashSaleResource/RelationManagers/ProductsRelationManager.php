<?php

namespace App\Filament\Resources\FlashSaleResource\RelationManagers;

use App\Filament\Resources\FlashSaleResource\Concerns\HasFlashSaleProductForm;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\FlashSale\FlashSaleProductSyncService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

class ProductsRelationManager extends RelationManager
{
    use HasFlashSaleProductForm;

    protected static string $relationship = 'products';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('ecommerce.flash_sale_products');
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('product_id')
                ->label(__('ecommerce.product'))
                ->options(fn (): array => Product::query()
                    ->published()
                    ->orderBy('name')
                    ->get()
                    ->mapWithKeys(fn (Product $product) => [
                        $product->id => self::productOptionLabel($product),
                    ])
                    ->all())
                ->searchable()
                ->required()
                ->live()
                ->disableOptionWhen(fn (int $value): bool => $this->getOwnerRecord()
                    ->products()
                    ->where('product_id', $value)
                    ->exists())
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
                }),
            Forms\Components\Select::make('product_variant_id')
                ->label(__('ecommerce.variants'))
                ->options(fn (Get $get): array => ProductVariant::query()
                    ->where('product_id', $get('product_id'))
                    ->pluck('sku', 'id')
                    ->all())
                ->visible(fn (Get $get): bool => filled($get('product_id'))
                    && ProductVariant::where('product_id', $get('product_id'))->exists())
                ->nullable(),
            Forms\Components\TextInput::make('sale_price')
                ->label(__('ecommerce.flash_price'))
                ->numeric()
                ->required()
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
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name')->label(__('ecommerce.product')),
                Tables\Columns\TextColumn::make('variant.sku')
                    ->label(__('ecommerce.variants'))
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('product.regular_price')
                    ->label(__('ecommerce.regular_price'))
                    ->money('EGP', locale: 'ar'),
                Tables\Columns\TextColumn::make('sale_price')
                    ->label(__('ecommerce.flash_price'))
                    ->money('EGP', locale: 'ar'),
                Tables\Columns\TextColumn::make('discount')
                    ->label(__('ecommerce.discount'))
                    ->state(fn ($record) => $record->discountPercent().'%'),
                Tables\Columns\TextColumn::make('quantity_sold')
                    ->label(__('ecommerce.quantity_sold')),
                Tables\Columns\TextColumn::make('stock_limit')
                    ->label(__('ecommerce.flash_quantity_limit'))
                    ->placeholder('∞'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label(__('ecommerce.flash_add_product')),
                Tables\Actions\Action::make('bulk_add')
                    ->label(__('ecommerce.flash_bulk_add_products'))
                    ->icon('heroicon-o-plus-circle')
                    ->form([
                        Forms\Components\Select::make('product_ids')
                            ->label(__('ecommerce.products'))
                            ->multiple()
                            ->options(fn (): array => Product::query()
                                ->published()
                                ->whereNotIn('id', $this->getOwnerRecord()->products()->pluck('product_id'))
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn (Product $product) => [
                                    $product->id => self::productOptionLabel($product),
                                ])
                                ->all())
                            ->searchable()
                            ->required(),
                        Forms\Components\TextInput::make('discount_percent')
                            ->label(__('ecommerce.flash_bulk_discount_percent'))
                            ->numeric()
                            ->default(15)
                            ->minValue(1)
                            ->maxValue(99)
                            ->suffix('%')
                            ->helperText(__('ecommerce.flash_bulk_discount_hint')),
                    ])
                    ->action(function (array $data): void {
                        $percent = (float) ($data['discount_percent'] ?? 15);
                        $factor = 1 - ($percent / 100);
                        $items = [];

                        foreach ($data['product_ids'] as $index => $productId) {
                            $product = Product::find($productId);
                            if (! $product) {
                                continue;
                            }

                            $base = (float) $product->regular_price;
                            $items[] = [
                                'product_id' => $product->id,
                                'sale_price' => round($base * $factor, 2),
                                'sort_order' => $index,
                            ];
                        }

                        if ($items === []) {
                            return;
                        }

                        $existing = $this->getOwnerRecord()->products()
                            ->get()
                            ->map(fn ($entry) => [
                                'id' => $entry->id,
                                'product_id' => $entry->product_id,
                                'product_variant_id' => $entry->product_variant_id,
                                'sale_price' => $entry->sale_price,
                                'stock_limit' => $entry->stock_limit,
                                'sort_order' => $entry->sort_order,
                            ])
                            ->all();

                        try {
                            app(FlashSaleProductSyncService::class)->sync(
                                $this->getOwnerRecord(),
                                array_merge($existing, $items)
                            );
                        } catch (ValidationException $e) {
                            throw $e;
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('sort_order');
    }
}
