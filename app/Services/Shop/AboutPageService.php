<?php

namespace App\Services\Shop;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class AboutPageService
{
    public function getContent(): array
    {
        return Setting::getValue('about_page_content', config('about'));
    }

    public function resolve(): array
    {
        return Cache::remember('shop.about_page', 3600, function (): array {
            $data = $this->getContent();
            $defaults = config('about');

            if (! is_array($data)) {
                $data = $defaults;
            }

            $intro = array_merge($defaults['intro'] ?? [], $data['intro'] ?? []);
            $ceo = array_merge($defaults['ceo'] ?? [], $data['ceo'] ?? []);
            $services = array_merge($defaults['services'] ?? [], $data['services'] ?? []);

            $introParagraphs = collect($intro['paragraphs'] ?? [])
                ->filter(fn ($paragraph) => filled($paragraph))
                ->values()
                ->all();

            $serviceItems = collect($services['items'] ?? [])
                ->map(function (array $item): array {
                    $bullets = collect($item['bullets'] ?? [])
                        ->filter(fn ($bullet) => filled($bullet))
                        ->values()
                        ->all();

                    return [
                        'title' => (string) ($item['title'] ?? ''),
                        'title_en' => (string) ($item['title_en'] ?? ''),
                        'image_url' => HomepageService::slideImageUrl($item['image'] ?? null, 960),
                        'bullets' => $bullets,
                    ];
                })
                ->filter(fn (array $item): bool => filled($item['title']) && $item['bullets'] !== [])
                ->values()
                ->all();

            return [
                'is_active' => (bool) ($data['is_active'] ?? $defaults['is_active'] ?? true),
                'page_title' => (string) ($data['page_title'] ?? $defaults['page_title'] ?? ''),
                'seo_title' => (string) ($data['seo_title'] ?? $defaults['seo_title'] ?? ''),
                'seo_description' => (string) ($data['seo_description'] ?? $defaults['seo_description'] ?? ''),
                'intro' => [
                    'is_active' => (bool) ($intro['is_active'] ?? true),
                    'eyebrow' => (string) ($intro['eyebrow'] ?? ''),
                    'title' => (string) ($intro['title'] ?? ''),
                    'paragraphs' => $introParagraphs,
                    'image_url' => HomepageService::slideImageUrl($intro['image'] ?? null, 1280),
                ],
                'ceo' => [
                    'is_active' => (bool) ($ceo['is_active'] ?? true),
                    'section_title' => (string) ($ceo['section_title'] ?? ''),
                    'quote' => (string) ($ceo['quote'] ?? ''),
                    'name' => (string) ($ceo['name'] ?? ''),
                    'title' => (string) ($ceo['title'] ?? ''),
                    'image_url' => HomepageService::slideImageUrl($ceo['image'] ?? null, 640),
                ],
                'services' => [
                    'is_active' => (bool) ($services['is_active'] ?? true),
                    'section_title' => (string) ($services['section_title'] ?? ''),
                    'section_subtitle' => (string) ($services['section_subtitle'] ?? ''),
                    'items' => $serviceItems,
                ],
            ];
        });
    }

    public function clearCache(): void
    {
        Cache::forget('shop.about_page');
    }
}
