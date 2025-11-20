<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\FinesApiService;
use App\Services\FinesProcessorService;
use Illuminate\Http\Request;
use App\Models\Vehicle;
use App\Models\Trailer;

class FineCheckController extends Controller
{
    protected FinesApiService $api;
    protected FinesProcessorService $processor;

    public function __construct(FinesApiService $api, FinesProcessorService $processor)
    {
        $this->api = $api;
        $this->processor = $processor;
    }

    /**
     * On-demand check: call external API and persist results.
     */
    public function check(string $plate)
    {
        $result = $this->api->checkByPlate($plate);

        $checkedable = Vehicle::where('plate_number', $plate)->first()
            ?? Trailer::where('plate_number', $plate)->first();

        // Persist in background (processor is synchronous here)
        $this->processor->process($plate, $checkedable, $result);

        return response()->json($result);
    }

    /**
     * Return recent fines stored in DB for this plate.
     */
    public function recent(string $plate)
    {
        $checkedable = Vehicle::where('plate_number', $plate)->first()
            ?? Trailer::where('plate_number', $plate)->first();

        if (!$checkedable) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $fines = $checkedable->trafficFines()->with('violations')->orderByDesc('created_at')->get();

        return response()->json(['status' => 'ok', 'fines' => $fines]);
    }
}
