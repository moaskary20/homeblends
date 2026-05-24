<?php

namespace App\Http\Controllers;

use App\Services\Settings\SettingsService;
use Illuminate\Http\Response;

class RobotsController extends Controller
{
    public function __invoke(SettingsService $settings): Response
    {
        $custom = trim((string) $settings->get('seo_robots_txt', ''));

        if ($custom !== '') {
            return response($custom, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }

        $sitemap = route('sitemap');

        $content = implode("\n", [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /api/',
            'Disallow: /checkout',
            'Disallow: /cart',
            'Disallow: /orders',
            '',
            'Sitemap: '.$sitemap,
        ]);

        return response($content, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
