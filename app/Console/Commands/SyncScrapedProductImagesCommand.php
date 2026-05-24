<?php

namespace App\Console\Commands;

use App\Services\ProductScraper\SyncScrapedProductImagesService;
use Illuminate\Console\Command;

class SyncScrapedProductImagesCommand extends Command
{
    protected $signature = 'products:sync-scraped-images
                            {--sku= : Single product SKU (e.g. ARIIKA-xxx)}
                            {--limit=20 : Max products when syncing all scraped}';

    protected $description = 'Re-download all gallery images for Ariika-scraped products';

    public function handle(SyncScrapedProductImagesService $service): int
    {
        $result = $service->sync(
            $this->option('sku'),
            (int) $this->option('limit')
        );

        foreach ($result['errors'] as $error) {
            $this->warn($error);
        }

        $this->info("Synced images for {$result['synced']} product(s).");

        return $result['synced'] > 0 || $result['errors'] === [] ? self::SUCCESS : self::FAILURE;
    }
}
