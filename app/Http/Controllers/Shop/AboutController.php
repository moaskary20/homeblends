<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Services\Shop\AboutPageService;
use App\Services\Shop\HomepageService;
use App\Services\Seo\SeoService;

class AboutController extends Controller
{
    public function __invoke(AboutPageService $about, HomepageService $homepage, SeoService $seo)
    {
        $content = $about->resolve();

        if (! $content['is_active']) {
            abort(404);
        }

        return view('shop.about', [
            'about' => $content,
            'partners' => $homepage->getContent()['partners'] ?? [],
            'seo' => $seo->forAbout(
                $content['seo_title'] ?: $content['page_title'],
                $content['seo_description'] ?: null,
            ),
        ]);
    }
}
