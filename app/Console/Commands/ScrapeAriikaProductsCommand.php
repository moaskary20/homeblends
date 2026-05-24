<?php

namespace App\Console\Commands;

use App\Services\ProductScraper\AriikaScraperService;
use App\Services\ProductScraper\ScrapedProductImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ScrapeAriikaProductsCommand extends Command
{
    protected $signature = 'products:scrape-ariika
                            {--collections=* : Collection handles (default: furniture set)}
                            {--limit=5 : Max products per collection}
                            {--dry-run : Preview without saving}
                            {--no-images : Skip image download}
                            {--skip-ping : Skip connectivity check before scrape}';

    protected $description = 'Scrape furniture products from ariika.com/ar (Shopify JSON API)';

    public function handle(AriikaScraperService $scraper, ScrapedProductImporter $importer): int
    {
        if (! $this->option('skip-ping')) {
            $this->line('Checking connectivity to ariika.com…');
            if (! $scraper->ping()) {
                $this->warn('Ping failed — continuing anyway (use --skip-ping to hide this check).');
            } else {
                $this->info('Connected to ariika.com/ar');
            }
        }

        $handles = $this->option('collections');
        if ($handles === [] || $handles === null) {
            $handles = array_keys($scraper->getFurnitureCollectionOptions());
        }

        $limit = max(1, (int) $this->option('limit'));
        $dryRun = (bool) $this->option('dry-run');

        $this->line('Collections: '.implode(', ', $handles));
        $this->line("Limit per collection: {$limit}");

        $items = $scraper->scrapeFurniture($handles, $limit);

        foreach ($scraper->getScrapeErrors() as $error) {
            $this->warn("Collection [{$error['handle']}]: {$error['message']}");
        }

        if ($items->isEmpty()) {
            $this->error('No products fetched. Check DNS/internet or retry with a single collection.');

            return self::FAILURE;
        }

        $this->table(
            ['SKU', 'Name', 'Category', 'Price', 'Stock'],
            $items->map(fn (array $p) => [
                $p['sku'],
                Str::limit($p['name'], 40),
                $p['category_name'],
                number_format($p['regular_price'], 0).' EGP',
                $p['stock_quantity'],
            ])->all()
        );

        $this->info("Fetched {$items->count()} unique products.");

        if ($dryRun) {
            $this->warn('Dry run — nothing saved.');

            return $scraper->getScrapeErrors()->isEmpty() ? self::SUCCESS : self::FAILURE;
        }

        $importer->import($items, ! $this->option('no-images'));

        $this->info("Created: {$importer->getCreatedCount()}, Updated: {$importer->getUpdatedCount()}");

        foreach ($importer->getErrors() as $error) {
            $this->warn($error);
        }

        return $importer->getErrors()->isEmpty() ? self::SUCCESS : self::FAILURE;
    }
}
