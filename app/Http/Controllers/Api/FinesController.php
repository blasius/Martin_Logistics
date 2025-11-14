<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Vehicle;

class FinesController extends Controller
{
    public function check($plateNumber)
    {
        $response = Http::withHeaders([
            'tin' => env('FINES_TIN'),
            'referer' => env('FINES_REFERER'),
            'rpk' => env('FINES_RPK'),
            'User-Agent' => 'Mozilla/5.0',
            'PlateNumber' => $plateNumber,
            'newrelic' => env('FINES_NEWRELIC'),
            'nls' => env('FINES_NLS'),
        ])->get(env('FINES_API_URL'));

        if (!$response->successful()) {
            return response()->json(['status' => false, 'message' => 'API error'], 500);
        }

        return $response->json();
    }

    public function listVehicles()
    {
        return Vehicle::select('id', 'plate_number')->get();
    }
}
