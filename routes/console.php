<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::call(fn () => \App\Services\OperationsHealth::record('scheduler', 'success', 'Scheduler heartbeat received.'))
    ->name('operations-heartbeat')->everyMinute();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('backup:database')
    ->sundays()
    ->at('01:00')
    ->withoutOverlapping();

Schedule::command('payments:reconcile')->everyTenMinutes()->withoutOverlapping();
Schedule::command('payments:settlements')->dailyAt('06:00')->withoutOverlapping();
Schedule::command('tuition:generate')->everyFifteenMinutes()->withoutOverlapping();
Schedule::command('tuition:remind')->dailyAt('08:00')->withoutOverlapping();
