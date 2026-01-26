<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VehicleInsurance;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class InsuranceController extends Controller
{
    public function index()
    {
        $today = now();

        // Get all records to split them
        $allInsurances = VehicleInsurance::with('vehicle:id,plate_number')
            ->orderBy('expiry_date', 'desc')
            ->get()
            ->map(function ($ins) use ($today) {
                $expiry = \Carbon\Carbon::parse($ins->expiry_date);
                $ins->days_left = (int) $today->diffInDays($expiry, false);
                return $ins;
            });

        return response()->json([
            // Active/Current Radar data
            'grounded' => $allInsurances->where('days_left', '<=', 0)->values(),
            'critical' => $allInsurances->whereBetween('days_left', [1, 14])->values(),
            'upcoming' => $allInsurances->where('days_left', '>', 14)->values(),

            // Audit Archive: Everything that is expired or historically logged
            'archive'  => $allInsurances->where('days_left', '<', 0)->values(),

            'vehicles' => Vehicle::select('id', 'plate_number')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'vehicle_id'    => 'required|exists:vehicles,id',
            'policy_number' => 'required|string|max:255',
            'provider_name' => 'nullable|string|max:255',
            'issue_date'    => 'required|date',
            'expiry_date'   => 'required|date|after:issue_date',
            'document'      => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB Limit
        ]);

        $path = $request->file('document')->store('insurances', 'public');

        VehicleInsurance::create([
            'vehicle_id'    => $request->vehicle_id,
            'policy_number' => $request->policy_number,
            'provider_name' => $request->provider_name,
            'issue_date'    => $request->issue_date,
            'expiry_date'   => $request->expiry_date,
            'document_path' => $path,
            'status'        => 'active'
        ]);

        // Return the fresh radar data immediately
        return $this->index();
    }
}
