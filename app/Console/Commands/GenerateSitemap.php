<?php

namespace App\Console\Commands;

use App\Http\Controllers\SitemapController;
use App\Services\Seo\SeoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate';

    protected $description = 'Generate and cache XML sitemap for SEO';

    public function handle(SeoService $seo): int
    {
        Cache::forget('sitemap.xml');

        $controller = app(SitemapController::class);
        $response = $controller($seo);
        $xml = $response->getContent();

        $path = public_path('sitemap.xml');
        file_put_contents($path, $xml);

        $this->info("Sitemap written to {$path} and cache refreshed.");

        return self::SUCCESS;
    }
}
