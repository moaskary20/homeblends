<?php

use App\Jobs\RecoverAbandonedCartsJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new RecoverAbandonedCartsJob)->daily();
Schedule::command('sitemap:generate')->daily();
Schedule::command('loyalty:expire')->daily();
