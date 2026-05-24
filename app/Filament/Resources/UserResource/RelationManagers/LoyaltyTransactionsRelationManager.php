<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Models\LoyaltyTransaction;
use App\Services\Loyalty\LoyaltyService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class LoyaltyTransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'loyaltyTransactions';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('ecommerce.loyalty_transactions');
    }

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('points')->label(__('ecommerce.loyalty_points')),
                Tables\Columns\TextColumn::make('type')
                    ->formatStateUsing(fn (LoyaltyTransaction $record): string => $record->typeLabel())
                    ->badge(),
                Tables\Columns\TextColumn::make('description')->limit(30),
                Tables\Columns\TextColumn::make('created_at')->dateTime('d/m/Y H:i'),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                Tables\Actions\Action::make('adjust_points')
                    ->label(__('ecommerce.adjust_points'))
                    ->icon('heroicon-o-plus-circle')
                    ->form([
                        Forms\Components\TextInput::make('points')
                            ->label(__('ecommerce.points_change'))
                            ->helperText(__('ecommerce.points_change_help'))
                            ->numeric()
                            ->required(),
                        Forms\Components\Textarea::make('description')
                            ->label(__('ecommerce.reason'))
                            ->required()
                            ->maxLength(500),
                    ])
                    ->action(function (array $data, LoyaltyService $loyaltyService): void {
                        $loyaltyService->adjustPoints(
                            $this->getOwnerRecord(),
                            (int) $data['points'],
                            $data['description'],
                            auth()->user()
                        );
                        Notification::make()
                            ->title(__('ecommerce.points_adjusted'))
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
