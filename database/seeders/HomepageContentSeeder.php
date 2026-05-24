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

        $this->command?->info('Homepage hero slides seeded ('.count($heroSlides).' slides).');
    }
}
