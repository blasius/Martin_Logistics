<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\Trailer;
use App\Models\Trip;
use App\Models\TrafficFine;
use Illuminate\Http\Request;

class ControlTowerController extends Controller
{
    public function getStats()
    {
        // 1. Get real counts from DB
        $vehicleCount = Vehicle::count();
        $trailerCount = Trailer::count();
        $activeTrips = Trip::where('status', 'active')->count();

        // 2. Fines reported in the last 24 hours
        $newFinesToday = TrafficFine::where('issued_at', '>=', now()->startOfDay())->count();

        return response()->json([
            'summary' => [
                [
                    'id' => 'all',
                    'title' => 'Total Fleet',
                    'value' => $vehicleCount + $trailerCount,
                    'icon' => 'lucide-layout-grid',
                    'color' => 'text-slate-600',
                    'activeClass' => 'bg-slate-800 text-white'
                ],
                [
                    'id' => 'vehicles',
                    'title' => 'Active Vehicles',
                    'value' => $vehicleCount,
                    'icon' => 'lucide-truck',
                    'color' => 'text-indigo-600',
                    'activeClass' => 'bg-indigo-600 text-white'
                ],
                [
                    'id' => 'trailers',
                    'title' => 'Active Trailers',
                    'value' => $trailerCount,
                    'icon' => 'lucide-trailer',
                    'color' => 'text-orange-600',
                    'activeClass' => 'bg-orange-600 text-white'
                ],
                [
                    'id' => 'trips',
                    'title' => 'Active Trips',
                    'value' => $activeTrips,
                    'icon' => 'lucide-route',
                    'color' => 'text-green-600',
                    'activeClass' => 'bg-green-600 text-white'
                ],
                [
                    'id' => 'fines',
                    'title' => 'New Fines',
                    'value' => $newFinesToday,
                    'icon' => 'lucide-alert-triangle',
                    'color' => 'text-red-600',
                    'activeClass' => 'bg-red-600 text-white'
                ]
            ],
            // We will populate these in the next steps
            'vehicles' => [],
            'alerts' => []
        ]);
    }
}
