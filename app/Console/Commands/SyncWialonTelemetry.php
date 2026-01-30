<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\WialonService;

class SyncWialonTelemetry extends Command
{
    protected $signature = 'telemetry:sync';
    protected $description = 'Fetch latest telemetry for all vehicles from Wialon';

    public function handle(WialonService $wialon)
    {
        $count = $wialon->syncTelemetry();
        $this->info("Processed $count vehicles.");
    }
}
