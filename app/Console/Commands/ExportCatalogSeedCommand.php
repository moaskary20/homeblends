<?php

namespace App\Console\Commands;

use App\Services\Shop\ProductCatalogExporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ExportCatalogSeedCommand extends Command
{
    protected $signature = 'products:export-catalog
                            {--path= : Output JSON path (default: database/seeders/data/catalog.json)}';

    protected $description = 'Export all products and categories to a JSON catalog for seeding';

    public function handle(ProductCatalogExporter $exporter): int
    {
        $count = \App\Models\Product::count();

        if ($count === 0) {
            $this->error('No products in the database. Import or scrape products first.');

            return self::FAILURE;
        }

        $path = $this->option('path')
            ?: database_path('seeders/data/catalog.json');

        $catalog = $exporter->export();
        File::ensureDirectoryExists(dirname($path));
        File::put(
            $path,
            json_encode($catalog, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n"
        );

        $this->info('Exported catalog seed data:');
        $this->line("  Products: ".count($catalog['products']));
        $this->line("  Categories: ".count($catalog['categories']));
        $this->line("  File: {$path}");

        return self::SUCCESS;
    }
}
