<?php

namespace App\Services\Shop;

use App\Models\Setting;
use App\Support\AppUrl;
use Illuminate\Support\Facades\Cache;

class DesignTeamPageService
{
    public function getContent(): array
    {
        return Setting::getValue('design_team_page_content', config('design-team'));
    }

    public function resolve(): array
    {
        return AppUrl::rewriteCachedValue(Cache::remember('shop.design_team_page', 3600, function (): array {
            $data = $this->getContent();
            $defaults = config('design-team');

            if (! is_array($data)) {
                $data = $defaults;
            }

            $hero = array_merge($defaults['hero'] ?? [], $data['hero'] ?? []);
            $howItWorks = array_merge($defaults['how_it_works'] ?? [], $data['how_it_works'] ?? []);
            $meetingWays = array_merge($defaults['meeting_ways'] ?? [], $data['meeting_ways'] ?? []);
            $services = array_merge($defaults['services'] ?? [], $data['services'] ?? []);
            $faq = array_merge($defaults['faq'] ?? [], $data['faq'] ?? []);

            $steps = collect($howItWorks['steps'] ?? [])
                ->map(fn (array $step): array => [
                    'title' => (string) ($step['title'] ?? ''),
                    'description' => (string) ($step['description'] ?? ''),
                    'image_url' => HomepageService::slideImageUrl($step['image'] ?? null, 960),
                ])
                ->filter(fn (array $step): bool => filled($step['title']))
                ->values()
                ->all();

            $meetingItems = collect($meetingWays['items'] ?? [])
                ->map(fn (array $item): array => [
                    'badge' => (string) ($item['badge'] ?? ''),
                    'type' => (string) ($item['type'] ?? ''),
                    'description' => (string) ($item['description'] ?? ''),
                    'cta' => (string) ($item['cta'] ?? ''),
                    'url' => $this->resolveUrl($item['url'] ?? ''),
                ])
                ->filter(fn (array $item): bool => filled($item['badge']) || filled($item['type']))
                ->values()
                ->all();

            $serviceItems = collect($services['items'] ?? [])
                ->map(fn (array $item): array => [
                    'icon' => (string) ($item['icon'] ?? 'clock'),
                    'title' => (string) ($item['title'] ?? ''),
                    'description' => (string) ($item['description'] ?? ''),
                    'note' => (string) ($item['note'] ?? ''),
                ])
                ->filter(fn (array $item): bool => filled($item['title']))
                ->values()
                ->all();

            $faqItems = collect($faq['items'] ?? [])
                ->map(fn (array $item): array => [
                    'question' => (string) ($item['question'] ?? ''),
                    'answer' => (string) ($item['answer'] ?? ''),
                ])
                ->filter(fn (array $item): bool => filled($item['question']))
                ->values()
                ->all();

            return [
                'is_active' => (bool) ($data['is_active'] ?? $defaults['is_active'] ?? true),
                'page_title' => (string) ($data['page_title'] ?? $defaults['page_title'] ?? ''),
                'seo_title' => (string) ($data['seo_title'] ?? $defaults['seo_title'] ?? ''),
                'seo_description' => (string) ($data['seo_description'] ?? $defaults['seo_description'] ?? ''),
                'hero' => [
                    'eyebrow' => (string) ($hero['eyebrow'] ?? ''),
                    'title' => (string) ($hero['title'] ?? ''),
                    'subtitle' => (string) ($hero['subtitle'] ?? ''),
                    'cta' => (string) ($hero['cta'] ?? ''),
                    'cta_url' => $this->resolveUrl($hero['cta_url'] ?? '/contact'),
                    'image_url' => HomepageService::slideImageUrl($hero['image'] ?? null, 1600),
                ],
                'how_it_works' => [
                    'is_active' => (bool) ($howItWorks['is_active'] ?? true),
                    'title' => (string) ($howItWorks['title'] ?? ''),
                    'subtitle' => (string) ($howItWorks['subtitle'] ?? ''),
                    'steps' => $steps,
                ],
                'meeting_ways' => [
                    'is_active' => (bool) ($meetingWays['is_active'] ?? true),
                    'title' => (string) ($meetingWays['title'] ?? ''),
                    'subtitle' => (string) ($meetingWays['subtitle'] ?? ''),
                    'items' => $meetingItems,
                ],
                'services' => [
                    'is_active' => (bool) ($services['is_active'] ?? true),
                    'title' => (string) ($services['title'] ?? ''),
                    'subtitle' => (string) ($services['subtitle'] ?? ''),
                    'items' => $serviceItems,
                ],
                'faq' => [
                    'is_active' => (bool) ($faq['is_active'] ?? true),
                    'title' => (string) ($faq['title'] ?? ''),
                    'items' => $faqItems,
                ],
            ];
        }));
    }

    public function clearCache(): void
    {
        Cache::forget('shop.design_team_page');
    }

    private function resolveUrl(string $url): string
    {
        if ($url === '') {
            return route('shop.contact');
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return AppUrl::normalize($url) ?? $url;
        }

        if (str_starts_with($url, '/')) {
            return url($url);
        }

        return $url;
    }
}
