<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Dagelijkse databaseback-up (storage/backups, 7 dagen bewaard). Vereist in DirectAdmin een cronjob
// die elke minuut draait: php /home/deb2003831/domains/databasehub.sorai.nl/laravel_app/artisan schedule:run
Schedule::command('boels:backup-database --retain=7')->dailyAt('03:15');
