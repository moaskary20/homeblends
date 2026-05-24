<?php

namespace Database\Seeders;

use App\Services\Settings\SettingsService;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(SettingsService $settings): void
    {
        foreach (SettingsService::MAIL_KEYS as $key => $default) {
            if ($settings->get($key) === null || $settings->get($key) === '') {
                $settings->set($key, $default, 'mail');
            }
        }

        foreach (SettingsService::NOTIFICATION_KEYS as $key => $default) {
            $settings->set($key, $default, 'notifications');
        }
    }
}
