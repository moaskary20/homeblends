<?php

namespace App\Filament\Pages\Concerns;

use App\Services\ProductScraper\ScrapedProductImporter;
use Illuminate\Support\Collection;

trait TracksScrapeProgress
{
    public bool $scrapeInProgress = false;

    public int $scrapeProgressPercent = 0;

    public string $scrapeProgressMessage = '';

    public string $scrapeProgressSource = '';

    /** @var Collection<int, array{handle: string, message: string}>|null */
    protected ?Collection $lastMergedScrapeErrors = null;

    protected function withScrapeProgress(string $sourceLabel, callable $callback): mixed
    {
        $this->beginScrapeProgress(
            $sourceLabel,
            __('ecommerce.scrape_progress_start', ['source' => $sourceLabel])
        );

        $success = false;

        try {
            $result = $callback();
            $success = true;

            return $result;
        } finally {
            $this->finishScrapeProgress($success);
        }
    }

    protected function beginScrapeProgress(string $sourceLabel, string $message): void
    {
        $this->scrapeInProgress = true;
        $this->scrapeProgressSource = $sourceLabel;
        $this->pulseScrapeProgress(5, $message);
    }

    protected function pulseScrapeProgress(int $percent, string $message): void
    {
        $this->scrapeProgressPercent = max(0, min(100, $percent));
        $this->scrapeProgressMessage = $message;

        $this->stream(
            to: 'scrape-progress',
            content: view('filament.pages.partials.scrape-progress-panel', [
                'percent' => $this->scrapeProgressPercent,
                'message' => $this->scrapeProgressMessage,
                'source' => $this->scrapeProgressSource,
                'active' => true,
            ])->render(),
            replace: true,
        );
    }

    protected function finishScrapeProgress(bool $success = true): void
    {
        if (! $this->scrapeInProgress) {
            return;
        }

        $this->pulseScrapeProgress(
            100,
            $success
                ? __('ecommerce.scrape_progress_done')
                : __('ecommerce.scrape_progress_failed')
        );

        $this->scrapeInProgress = false;
    }

    /**
     * @param  array<int, string>  $collections
     * @return array{0: Collection<int, array<string, mixed>>, 1: object}
     */
    protected function fetchCollectionsWithProgress(
        object $scraper,
        array $collections,
        int $limit,
        string $sourceLabel,
    ): array {
        if (! $this->scrapeInProgress) {
            $this->beginScrapeProgress(
                $sourceLabel,
                __('ecommerce.scrape_progress_start', ['source' => $sourceLabel])
            );
        }

        $all = collect();
        $allErrors = collect();
        $options = $this->scraperCollectionOptions($scraper);
        $total = max(1, count($collections));

        foreach ($collections as $index => $handle) {
            $label = $options[$handle] ?? $handle;
            $this->pulseScrapeProgress(
                (int) ((($index + 0.5) / $total) * 65),
                __('ecommerce.scrape_progress_fetching', [
                    'source' => $sourceLabel,
                    'collection' => $label,
                    'current' => $index + 1,
                    'total' => $total,
                ])
            );

            $method = method_exists($scraper, 'scrapeFurniture')
                ? 'scrapeFurniture'
                : 'scrapeCollections';

            $batch = $scraper->{$method}([$handle], $limit);
            $all = $all->merge($batch);
            $allErrors = $allErrors->merge($scraper->getScrapeErrors());
        }

        $this->lastMergedScrapeErrors = $allErrors;

        $this->pulseScrapeProgress(
            68,
            __('ecommerce.scrape_progress_fetched', ['count' => $all->count()])
        );

        return [
            $all->unique(fn (array $p) => $p['sku'] ?? $p['slug'] ?? md5(json_encode($p)))->values(),
            $scraper,
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $items
     */
    protected function importWithProgress(Collection $items, bool $downloadImages): ScrapedProductImporter
    {
        $importer = app(ScrapedProductImporter::class);
        $total = max(1, $items->count());

        $importer->import($items, $downloadImages, function (int $current, int $totalItems, string $sku) use ($total): void {
            $ratio = $current / max(1, $totalItems);
            $percent = 70 + (int) ($ratio * 28);
            $this->pulseScrapeProgress(
                $percent,
                __('ecommerce.scrape_progress_importing', [
                    'current' => $current,
                    'total' => $totalItems,
                    'sku' => $sku,
                ])
            );
        });

        $this->finishScrapeProgress(true);

        return $importer;
    }

    /** @return array<string, string> */
    protected function scraperCollectionOptions(object $scraper): array
    {
        if (method_exists($scraper, 'getCollectionOptions')) {
            return $scraper->getCollectionOptions();
        }

        if (method_exists($scraper, 'getFurnitureCollectionOptions')) {
            return $scraper->getFurnitureCollectionOptions();
        }

        return [];
    }

    /**
     * @return Collection<int, array{handle: string, message: string}>
     */
    protected function mergedScrapeErrors(object $scraper): Collection
    {
        return $this->lastMergedScrapeErrors ?? $scraper->getScrapeErrors();
    }
}
