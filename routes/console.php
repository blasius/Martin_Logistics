<?php

use App\Jobs\SyncWialonUnitsJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

//Schedule::job(new SyncWialonUnitsJob)->everyTwoMinutes()->withoutOverlapping();
Schedule::command('telemetry:sync')->everyTwoMinutes();
Schedule::command('telemetry:archive')->dailyAt('02:00');
Schedule::command('telemetry:maintain-partitions')->monthlyOn(1, '00:00');
