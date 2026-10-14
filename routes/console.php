<?php

use App\Jobs\SyncWialonUnitsJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

//Schedule::job(new SyncWialonUnitsJob)->everyTwoMinutes()->withoutOverlapping();
Schedule::command('telemetry:sync')->everyMinute()->withoutOverlapping();
Schedule::command('telemetry:archive')->dailyAt('02:00')->withoutOverlapping();
Schedule::command('telemetry:maintain-partitions')->monthlyOn(1, '00:00')->withoutOverlapping();
Schedule::command('documents:check-expiry --days=30')->dailyAt('08:00');
Schedule::command('tickets:process-escalations')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('payments:send-reminders')->dailyAt('07:00');
Schedule::command('fuel:reconcile')->dailyAt('00:30')->withoutOverlapping();
Schedule::command('fines:dispatch --only-new --hours=24')->dailyAt('03:00')->withoutOverlapping();
Schedule::command('fines:aggregate')->dailyAt('03:45')->withoutOverlapping();
Schedule::command('fines:dispatch')->weeklyOn(1, '03:30')->withoutOverlapping();
Schedule::command('access:review')->weeklyOn(1, '06:00')->withoutOverlapping();
Schedule::command('accounting:auto-post')->dailyAt('04:00')->withoutOverlapping();
Schedule::command('wialon:sync-vehicles')->dailyAt('01:00')->withoutOverlapping();
Schedule::command('fuel:prepopulate')->weeklyOn(1, '02:00')->withoutOverlapping();

/*
| Manual / diagnostic commands (intentionally unscheduled):
|   wialon:test-connection     - connectivity diagnostic, run on demand
|   telemetry:manage-partitions - superseded by telemetry:maintain-partitions; its ADD PARTITION
|                                 approach conflicts with the p_future MAXVALUE partition
*/

