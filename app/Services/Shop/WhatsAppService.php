<?php

namespace App\Services\Shop;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class WhatsAppService
{
    public function getSettings(): array
    {
        $stored = Setting::getValue('whatsapp_settings');

        if (! is_array($stored)) {
            return config('whatsapp', []);
        }

        return array_merge(config('whatsapp', []), $stored);
    }

    public function resolve(): array
    {
        return Cache::remember('shop.whatsapp', 3600, function (): array {
            $data = $this->getSettings();
            $defaults = config('whatsapp', []);

            $isActive = (bool) ($data['is_active'] ?? $defaults['is_active'] ?? true);
            $phone = (string) ($data['phone'] ?? $defaults['phone'] ?? '');
            $digits = self::normalizePhone($phone);

            return [
                'is_active' => $isActive,
                'phone' => $phone,
                'digits' => $digits,
                'url' => $digits !== '' ? 'https://wa.me/'.$digits : '',
            ];
        });
    }

    public static function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '0') && strlen($digits) === 11) {
            return '20'.substr($digits, 1);
        }

        return $digits;
    }

    public function clearCache(): void
    {
        Cache::forget('shop.whatsapp');
    }
}
