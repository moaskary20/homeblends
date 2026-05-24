<?php

namespace Database\Seeders;

use App\Services\Shop\ProductCatalogImporter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class CatalogProductsSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/catalog.json');

        if (! File::exists($path)) {
            $this->command?->warn("Catalog seed file not found: {$path}");
            $this->command?->warn('Run: php artisan products:export-catalog (after products exist locally)');

            return;
        }

        $catalog = json_decode(File::get($path), true);

        if (! is_array($catalog)) {
            $this->command?->error('Invalid catalog.json');

            return;
        }

        $importer = app(ProductCatalogImporter::class);
        $importer->import($catalog);

        $this->command?->info(sprintf(
            'Catalog seeded: %d categories, %d products created, %d updated.',
            $importer->getCategoriesUpserted(),
            $importer->getProductsCreated(),
            $importer->getProductsUpdated(),
        ));
    }
}
