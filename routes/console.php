<?php

use App\Jobs\SyncWialonUnitsJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

//Schedule::job(new SyncWialonUnitsJob)->everyTwoMinutes()->withoutOverlapping();
Schedule::command('telemetry:sync')->everyThreeMinutes();
Schedule::command('telemetry:archive')->dailyAt('02:00');
