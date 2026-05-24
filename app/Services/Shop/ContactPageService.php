<?php

namespace App\Services\Shop;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class ContactPageService
{
    public function getContent(): array
    {
        return Setting::getValue('contact_page_content', config('contact'));
    }

    public function resolve(): array
    {
        return Cache::remember('shop.contact_page', 3600, function (): array {
            $data = $this->getContent();
            $defaults = config('contact');

            if (! is_array($data)) {
                $data = $defaults;
            }

            $hero = array_merge($defaults['hero'] ?? [], $data['hero'] ?? []);
            $info = array_merge($defaults['info'] ?? [], $data['info'] ?? []);
            $map = array_merge($defaults['map'] ?? [], $data['map'] ?? []);
            $form = array_merge($defaults['form'] ?? [], $data['form'] ?? []);

            $social = collect($data['social'] ?? $defaults['social'] ?? [])
                ->map(fn (array $item): array => [
                    'label' => (string) ($item['label'] ?? ''),
                    'url' => (string) ($item['url'] ?? ''),
                    'icon' => (string) ($item['icon'] ?? 'facebook'),
                ])
                ->filter(fn (array $item): bool => filled($item['url']))
                ->values()
                ->all();

            $gallery = collect($data['gallery'] ?? $defaults['gallery'] ?? [])
                ->map(fn (array $item): array => [
                    'label' => (string) ($item['label'] ?? ''),
                    'image_url' => HomepageService::slideImageUrl($item['image'] ?? null, 720),
                ])
                ->filter(fn (array $item): bool => filled($item['image_url']))
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
                    'image_url' => HomepageService::slideImageUrl($hero['image'] ?? null, 1400),
                    'accent_image_url' => HomepageService::slideImageUrl($hero['accent_image'] ?? null, 640),
                ],
                'info' => [
                    'address_label' => (string) ($info['address_label'] ?? ''),
                    'address' => (string) ($info['address'] ?? ''),
                    'phone_label' => (string) ($info['phone_label'] ?? ''),
                    'phone' => (string) ($info['phone'] ?? ''),
                    'phone_link' => (string) ($info['phone_link'] ?? ''),
                    'email_label' => (string) ($info['email_label'] ?? ''),
                    'email' => (string) ($info['email'] ?? ''),
                    'social_title' => (string) ($info['social_title'] ?? ''),
                ],
                'map' => [
                    'is_active' => (bool) ($map['is_active'] ?? true),
                    'embed_url' => (string) ($map['embed_url'] ?? ''),
                    'link_url' => (string) ($map['link_url'] ?? ''),
                ],
                'form' => [
                    'title' => (string) ($form['title'] ?? ''),
                    'subtitle' => (string) ($form['subtitle'] ?? ''),
                    'recipient_email' => (string) ($form['recipient_email'] ?? ''),
                ],
                'social' => $social,
                'gallery' => $gallery,
            ];
        });
    }

    public function clearCache(): void
    {
        Cache::forget('shop.contact_page');
    }
}
