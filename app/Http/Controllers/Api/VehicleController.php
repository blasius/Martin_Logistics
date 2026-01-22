<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\Trailer;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function index(Request $request)
    {
        $query = Vehicle::with(['currentAssignment.trailer']);

        if ($request->search) {
            $query->where('plate_number', 'like', '%' . $request->search . '%')
                ->orWhere('make', 'like', '%' . $request->search . '%');
        }

        $vehicles = $query->latest()->paginate(15);

        return response()->json([
            'vehicles' => $vehicles,
            'stats' => [
                'total_vehicles' => Vehicle::count(),
                'total_trailers' => Trailer::count(),
                'active_fleet' => Vehicle::where('status', 'active')->count(),
                'in_maintenance' => Vehicle::where('status', 'maintenance')->count(),
            ]
        ]);
    }
}
