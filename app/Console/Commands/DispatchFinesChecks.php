<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Vehicle;
use App\Models\Trailer;
use App\Jobs\CheckPlateJob;

class DispatchFinesChecks extends Command
{
    protected $signature = 'fines:dispatch {--only-new : Dispatch only plates not checked recently}';
    protected $description = 'Dispatch fines check jobs for vehicles and trailers with randomized spacing';

    public function handle(): int
    {
        $vehicles = Vehicle::select('plate_number')->get()->pluck('plate_number')->toArray();
        $trailers = Trailer::select('plate_number')->get()->pluck('plate_number')->toArray();

        $plates = array_values(array_filter(array_merge($vehicles, $trailers)));
        shuffle($plates);

        $delaySeconds = 0;

        foreach ($plates as $plate) {
            $increment = rand(20, 35);
            $delaySeconds += $increment;

            $existsAsVehicle = Vehicle::where('plate_number', $plate)->exists();
            $modelType = $existsAsVehicle ? 'vehicle' : 'trailer';

            CheckPlateJob::dispatch($plate, $modelType)->delay(now()->addSeconds($delaySeconds));
            $this->info("Queued {$plate} (type: {$modelType}) at +{$delaySeconds}s");
        }

        $this->info('Dispatched fines checks for '.count($plates).' plates.');

        return 0;
    }
}
