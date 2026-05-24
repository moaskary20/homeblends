<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class HomepageContentSeeder extends Seeder
{
    public function run(): void
    {
        $heroSlides = config('homepage.hero_slides', []);

        if ($heroSlides !== []) {
            Setting::setValue('homepage_hero_slides', $heroSlides, 'homepage');
        }

        $customerReviews = config('homepage.customer_reviews', []);
        if ($customerReviews !== []) {
            Setting::setValue('homepage_customer_reviews', $customerReviews, 'homepage');
        }

        \Illuminate\Support\Facades\Cache::forget('shop.customer_reviews');

        $this->command?->info('Homepage hero slides seeded ('.count($heroSlides).' slides).');
        $this->command?->info('Homepage customer reviews seeded ('.count($customerReviews['items'] ?? []).' items).');
    }
}
