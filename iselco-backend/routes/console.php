<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule expired role revocation (runs daily at midnight)
Schedule::command('roles:revoke-expired')->daily();

// Schedule department sync (runs daily at 2 AM)
Schedule::command('sync:departments')->dailyAt('02:00');
