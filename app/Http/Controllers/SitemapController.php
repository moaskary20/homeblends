<?php

namespace App\Http\Controllers;

use App\Services\Seo\SeoService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public function __invoke(SeoService $seo): Response
    {
        $xml = Cache::remember('sitemap.xml', 3600, function () use ($seo) {
            $entries = $seo->sitemapEntries();

            $urls = collect($entries)->map(function (array $entry) {
                $lastmod = $entry['lastmod']?->toAtomString() ?? now()->toAtomString();

                return sprintf(
                    "  <url>\n    <loc>%s</loc>\n    <lastmod>%s</lastmod>\n    <changefreq>%s</changefreq>\n    <priority>%.1f</priority>\n  </url>",
                    htmlspecialchars($entry['loc'], ENT_XML1),
                    $lastmod,
                    $entry['changefreq'],
                    $entry['priority']
                );
            })->implode("\n");

            return '<?xml version="1.0" encoding="UTF-8"?>'."\n"
                .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n"
                .$urls."\n"
                .'</urlset>';
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
