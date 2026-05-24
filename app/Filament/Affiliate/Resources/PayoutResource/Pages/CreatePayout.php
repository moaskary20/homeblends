<?php

namespace App\Filament\Affiliate\Resources\PayoutResource\Pages;

use App\Filament\Affiliate\Resources\PayoutResource;
use App\Services\Affiliate\AffiliatePayoutService;
use Filament\Resources\Pages\CreateRecord;

class CreatePayout extends CreateRecord
{
    protected static string $resource = PayoutResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['affiliate_id'] = auth()->user()->affiliate->id;
        $data['currency'] = config('ecommerce.currency', 'EGP');

        return $data;
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        return app(AffiliatePayoutService::class)->request(
            auth()->user()->affiliate,
            (float) $data['amount'],
            $data['notes'] ?? null
        );
    }
}
