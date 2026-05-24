<?php

namespace App\Console\Commands;

use App\Services\Shop\AttachStoredProductImagesService;
use Illuminate\Console\Command;

class AttachStoredProductImagesCommand extends Command
{
    protected $signature = 'products:attach-stored-images';

    protected $description = 'Link existing files in storage/app/public/products/scraped to products by SKU';

    public function handle(AttachStoredProductImagesService $service): int
    {
        $attached = $service->attachAll();
        $this->info("Attached stored images for {$attached} product(s).");

        return self::SUCCESS;
    }
}
