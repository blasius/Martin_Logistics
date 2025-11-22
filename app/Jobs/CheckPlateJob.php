<?php

namespace App\Jobs;

use App\Services\FinesApiService;
use App\Services\FinesProcessorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Vehicle;
use App\Models\Trailer;

class CheckPlateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $plate;
    public string $type; // 'vehicle' or 'trailer'

    public function __construct(string $plate, string $type = 'vehicle')
    {
        $this->plate = $plate;
        $this->type = $type;
    }

    public function handle(FinesApiService $api, FinesProcessorService $processor): void
    {
        $checkedable = null;
        if ($this->type === 'vehicle') {
            $checkedable = Vehicle::where('plate_number', $this->plate)->first();
        } else {
            $checkedable = Trailer::where('plate_number', $this->plate)->first();
        }

        $result = $api->checkByPlate($this->plate);
        $processor->process($this->plate, $checkedable, $result);
    }
}
