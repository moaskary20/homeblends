<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function (Actions\DeleteAction $action): void {
                    if (UserResource::isProtectedUser($this->record)) {
                        $action->halt();
                    }
                }),
        ];
    }

    protected function afterSave(): void
    {
        /** @var User $user */
        $user = $this->record;
        UserResource::syncAdminRole($user);
    }
}
