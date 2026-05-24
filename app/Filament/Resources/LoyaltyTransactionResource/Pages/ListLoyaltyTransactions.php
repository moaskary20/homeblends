<?php

namespace App\Filament\Resources\LoyaltyTransactionResource\Pages;

use App\Filament\Resources\LoyaltyTransactionResource;
use App\Models\User;
use App\Services\Loyalty\LoyaltyService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListLoyaltyTransactions extends ListRecords
{
    protected static string $resource = LoyaltyTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('adjust_points')
                ->label(__('ecommerce.adjust_points_for_user'))
                ->icon('heroicon-o-plus-circle')
                ->form([
                    Forms\Components\Select::make('user_id')
                        ->label(__('ecommerce.customer'))
                        ->searchable()
                        ->required()
                        ->getSearchResultsUsing(fn (string $search): array => User::query()
                            ->where(fn ($q) => $q
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%"))
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(fn (User $user) => [
                                $user->id => "{$user->name} — {$user->email} ({$user->loyalty_points} ".__('ecommerce.points').')',
                            ])
                            ->all())
                        ->getOptionLabelUsing(fn ($value): ?string => User::query()
                            ->find($value)
                            ?->name),
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
                    $user = User::query()->findOrFail($data['user_id']);

                    try {
                        $loyaltyService->adjustPoints(
                            $user,
                            (int) $data['points'],
                            $data['description'],
                            auth()->user()
                        );
                    } catch (\InvalidArgumentException $e) {
                        Notification::make()
                            ->title($e->getMessage())
                            ->danger()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title(__('ecommerce.points_adjusted'))
                        ->success()
                        ->send();
                }),
        ];
    }
}
