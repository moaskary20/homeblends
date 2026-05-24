<?php

namespace App\Console\Commands;

use App\Services\Loyalty\LoyaltyService;
use Illuminate\Console\Command;

class ExpireLoyaltyPointsCommand extends Command
{
    protected $signature = 'loyalty:expire';

    protected $description = 'Expire loyalty points past their expiry date';

    public function handle(LoyaltyService $loyaltyService): int
    {
        $count = $loyaltyService->expirePoints();
        $this->info("Expired {$count} loyalty points.");

        return self::SUCCESS;
    }
}
