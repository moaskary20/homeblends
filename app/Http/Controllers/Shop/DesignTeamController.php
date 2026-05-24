<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Services\Shop\DesignTeamPageService;
use App\Services\Seo\SeoService;

class DesignTeamController extends Controller
{
    public function __invoke(DesignTeamPageService $designTeam, SeoService $seo)
    {
        $content = $designTeam->resolve();

        if (! $content['is_active']) {
            abort(404);
        }

        return view('shop.design-team', [
            'designTeam' => $content,
            'seo' => $seo->forDesignTeam(
                $content['seo_title'] ?: $content['page_title'],
                $content['seo_description'] ?: null,
            ),
        ]);
    }
}
