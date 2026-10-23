<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Jadwal pencadangan otomatis PIB
Schedule::command('pib:backup-scheduled')->everyTwoHours();
Schedule::command('backup:clean')->daily();
