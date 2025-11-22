<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Vehicle;
use App\Models\Trailer;
use App\Jobs\CheckPlateJob;

class DispatchFinesChecks extends Command
{
    protected $signature = 'fines:dispatch {--only-new : only dispatch plates not checked in last X hours} {--hours=24 : only-new threshold in hours}';
    protected $description = 'Dispatch fines check jobs for vehicles and trailers with randomized spacing (20-35s)';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $vehicles = Vehicle::select('plate_number','last_fine_check_at')->get();
        $trailers = Trailer::select('plate_number','last_fine_check_at')->get();

        $plates = [];
        foreach ($vehicles as $v) {
            $plates[] = ['plate' => $v->plate_number, 'type' => 'vehicle', 'last' => $v->last_fine_check_at];
        }
        foreach ($trailers as $t) {
            $plates[] = ['plate' => $t->plate_number, 'type' => 'trailer', 'last' => $t->last_fine_check_at];
        }

        shuffle($plates);

        $delay = 0;
        foreach ($plates as $p) {
            if ($this->option('only-new') && $p['last'] && now()->diffInHours($p['last']) < $hours) {
                continue;
            }

            $delay += rand(20, 35);
            CheckPlateJob::dispatch($p['plate'], $p['type'])->delay(now()->addSeconds($delay));
            $this->info("Queued {$p['plate']} ({$p['type']}) +{$delay}s");
        }

        $this->info('Dispatch completed.');
        return 0;
    }
}
