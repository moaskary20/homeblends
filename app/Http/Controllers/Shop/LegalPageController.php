<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Services\Seo\SeoService;
use App\Services\Shop\LegalPageService;
use Illuminate\Http\Request;

class LegalPageController extends Controller
{
    public function __invoke(Request $request, LegalPageService $legal, SeoService $seo)
    {
        $key = $legal->keyFromRouteName($request->route()->getName());
        if ($key === null) {
            abort(404);
        }

        $page = $legal->resolve($key);

        if (! ($page['is_active'] ?? true)) {
            abort(404);
        }

        return view('shop.legal', [
            'page' => $page,
            'legalLinks' => $legal->homepageLinks(),
            'seo' => $seo->forLegalPage(
                $page['seo_title'] ?: $page['page_title'],
                $page['seo_description'] ?: null,
                route($request->route()->getName()),
            ),
        ]);
    }
}
