<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductReviewResource\Pages;
use App\Models\ProductReview;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductReviewResource extends Resource
{
    protected static ?string $model = ProductReview::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): ?string
    {
        return __('ecommerce.catalog');
    }

    public static function getNavigationLabel(): string
    {
        return __('ecommerce.reviews');
    }

    public static function getModelLabel(): string
    {
        return __('ecommerce.review');
    }

    public static function getPluralModelLabel(): string
    {
        return __('ecommerce.reviews');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('product_id')
                ->relationship('product', 'name')
                ->disabled(),
            Forms\Components\Select::make('user_id')
                ->relationship('user', 'name')
                ->disabled(),
            Forms\Components\Select::make('rating')
                ->label(__('ecommerce.rating'))
                ->options([1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5'])
                ->required(),
            Forms\Components\Textarea::make('comment')->label(__('ecommerce.comment'))->columnSpanFull(),
            Forms\Components\Toggle::make('is_approved')->label(__('ecommerce.is_approved')),
            Forms\Components\Toggle::make('is_verified_purchase')->label(__('ecommerce.verified_purchase')),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name')->label(__('ecommerce.product')),
                Tables\Columns\TextColumn::make('user.name')->label(__('ecommerce.customer')),
                Tables\Columns\TextColumn::make('rating')->label(__('ecommerce.rating')),
                Tables\Columns\IconColumn::make('is_approved')->label(__('ecommerce.is_approved'))->boolean(),
                Tables\Columns\IconColumn::make('is_verified_purchase')->label(__('ecommerce.verified_purchase'))->boolean(),
                Tables\Columns\TextColumn::make('created_at')->label(__('ecommerce.created_at'))->dateTime('d/m/Y'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_approved')->label(__('ecommerce.is_approved')),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label(__('ecommerce.approve'))
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (ProductReview $record) => ! $record->is_approved)
                    ->action(fn (ProductReview $record) => app(\App\Services\Review\ReviewService::class)->approve($record)),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductReviews::route('/'),
            'edit' => Pages\EditProductReview::route('/{record}/edit'),
        ];
    }
}
