<?php

namespace App\Filament\Resources\InstallmentContractResource\Pages;

use App\Filament\Resources\InstallmentContractResource;
use Filament\Resources\Pages\ViewRecord;

class ViewInstallmentContract extends ViewRecord
{
    protected static string $resource = InstallmentContractResource::class;

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }
}
