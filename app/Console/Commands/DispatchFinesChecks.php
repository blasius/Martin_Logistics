<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Vehicle;
use App\Models\Trailer;
use App\Jobs\CheckPlateJob;

class DispatchFinesChecks extends Command
{
    protected $signature = 'fines:dispatch {--only-new : only dispatch plates not checked recently} {--hours=24 : threshold in hours for --only-new}';
    protected $description = 'Dispatch fines check jobs for all vehicles and trailers with randomized spacing (20-35s)';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');

        $plates = collect()
            ->concat(Vehicle::select('plate_number','last_fine_check_at')->get()->map(fn($v)=> ['plate'=>$v->plate_number,'type'=>'vehicle','last'=>$v->last_fine_check_at]))
            ->concat(Trailer::select('plate_number','last_fine_check_at')->get()->map(fn($t)=> ['plate'=>$t->plate_number,'type'=>'trailer','last'=>$t->last_fine_check_at]))
            ->shuffle();

        $delay = 0;
        foreach ($plates as $p) {
            if ($this->option('only-new') && $p['last'] && now()->diffInHours($p['last']) < $hours) {
                continue;
            }
            $delay += rand(20, 35);
            CheckPlateJob::dispatch($p['plate'], $p['type'])->delay(now()->addSeconds($delay));
            $this->info("Queued {$p['plate']} ({$p['type']}) +{$delay}s");
        }

        $this->info('Fines dispatch completed.');
        return 0;
    }
}
