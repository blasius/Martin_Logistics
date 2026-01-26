<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VehicleInspection;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Carbon\Carbon;

class InspectionController extends Controller
{
    public function index()
    {
        $today = now()->startOfDay();

        $inspections = VehicleInspection::with('vehicle:id,plate_number')
            ->orderBy('completed_date', 'asc') // Expiry order
            ->get()
            ->map(function ($insp) use ($today) {
                // Mapping: completed_date acts as the Expiry Date
                $expiry = Carbon::parse($insp->completed_date);
                $insp->days_left = (int) $today->diffInDays($expiry, false);
                return $insp;
            });

        return response()->json([
            'grounded' => $inspections->where('days_left', '<=', 0)->values(),
            'critical' => $inspections->whereBetween('days_left', [1, 14])->values(),
            'upcoming' => $inspections->where('days_left', '>', 14)->values(),
            'archive'  => $inspections->where('days_left', '<', 0)->values(),
            'vehicles' => Vehicle::select('id', 'plate_number')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'vehicle_id'     => 'required|exists:vehicles,id',
            'scheduled_date' => 'required|date', // Acts as Issue Date
            'completed_date' => 'required|date|after:scheduled_date', // Acts as Expiry Date
            'inspector_name' => 'nullable|string',
            'document'       => 'required|file|mimes:pdf,jpg,png|max:5120',
        ]);

        $path = $request->file('document')->store('inspections', 'public');

        VehicleInspection::create([
            'vehicle_id'     => $request->vehicle_id,
            'scheduled_date' => $request->scheduled_date,
            'completed_date' => $request->completed_date,
            'inspector_name' => $request->inspector_name,
            'document_path'  => $path,
            'status'         => 'completed'
        ]);

        return $this->index();
    }
}
