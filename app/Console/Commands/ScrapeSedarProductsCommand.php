<?php

namespace App\Console\Commands;

use App\Services\ProductScraper\ScrapedProductImporter;
use App\Services\ProductScraper\SedarScraperService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ScrapeSedarProductsCommand extends Command
{
    protected $signature = 'products:scrape-sedar
                            {--collections=* : Collection handles (default: all curtain sections)}
                            {--limit=5 : Max products per collection}
                            {--dry-run : Preview without saving}
                            {--no-images : Skip image download}
                            {--skip-ping : Skip connectivity check before scrape}';

    protected $description = 'Scrape curtains/textiles from sedarglobal.com (Sedar Global)';

    public function handle(SedarScraperService $scraper, ScrapedProductImporter $importer): int
    {
        if (! $this->option('skip-ping')) {
            $this->line('Checking connectivity to sedarglobal.com…');
            if (! $scraper->ping()) {
                $this->warn('Ping failed — continuing anyway (use --skip-ping to hide this check).');
            } else {
                $this->info('Connected to sedarglobal.com');
            }
        }

        $handles = $this->option('collections');
        if ($handles === [] || $handles === null) {
            $handles = array_keys($scraper->getCollectionOptions());
        }

        $limit = max(1, min(50, (int) $this->option('limit')));
        $dryRun = (bool) $this->option('dry-run');

        $this->line('Collections: '.implode(', ', $handles));
        $this->line("Limit per collection: {$limit}");

        $items = $scraper->scrapeCollections($handles, $limit);

        foreach ($scraper->getScrapeErrors() as $error) {
            $this->warn("Collection [{$error['handle']}]: {$error['message']}");
        }

        if ($items->isEmpty()) {
            $this->error('No products fetched. Check internet connection or retry later.');

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
