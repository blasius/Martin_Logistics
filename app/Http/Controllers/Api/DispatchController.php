<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\Driver;
use App\Models\Trailer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\Exports\DispatchExport;
use Maatwebsite\Excel\Facades\Excel;

class DispatchController extends Controller
{
    public function index()
    {
        // 1. Get Vehicles and manually attach the current Driver (User)
        $vehicles = Vehicle::with(['currentAssignment.trailer'])->get()->map(function ($vehicle) {
            $currentDriver = DB::table('users')
                ->join('driver_vehicle_assignments', 'users.id', '=', 'driver_vehicle_assignments.driver_id')
                ->where('driver_vehicle_assignments.vehicle_id', $vehicle->id)
                ->whereNull('driver_vehicle_assignments.end_date')
                ->select('users.id', 'users.name')
                ->first();

            $vehicle->current_driver = $currentDriver;
            return $vehicle;
        });

        // 2. Available Drivers: Get drivers whose user_id is NOT in an active assignment
        $assignedUserIds = DB::table('driver_vehicle_assignments')
            ->whereNull('end_date')
            ->pluck('driver_id');

        $availableDrivers = Driver::with('user')
            ->whereNotIn('user_id', $assignedUserIds)
            ->get();

        // 3. Available Trailers: Get trailers NOT in an active assignment
        $assignedTrailerIds = DB::table('trailer_assignments')
            ->whereNull('unassigned_at')
            ->pluck('trailer_id');

        $availableTrailers = Trailer::where('status', 'active')
            ->whereNotIn('id', $assignedTrailerIds)
            ->get();

        return response()->json([
            'vehicles' => $vehicles,
            'available_drivers' => $availableDrivers,
            'available_trailers' => $availableTrailers,
        ]);
    }

    public function pair(Request $request)
    {
        $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'driver_id'  => 'nullable|exists:drivers,id',
            'trailer_id' => 'nullable|exists:trailers,id',
        ]);

        return DB::transaction(function () use ($request) {
            $now = now();

            // Handle Trailer Assignment
            if ($request->has('trailer_id')) {
                DB::table('trailer_assignments')
                    ->where('vehicle_id', $request->vehicle_id)
                    ->whereNull('unassigned_at')
                    ->update(['unassigned_at' => $now]);

                if ($request->trailer_id) {
                    DB::table('trailer_assignments')->insert([
                        'vehicle_id'  => $request->vehicle_id,
                        'trailer_id'  => $request->trailer_id,
                        'assigned_at' => $now,
                        'created_at'  => $now,
                        'updated_at'  => $now,
                    ]);
                }
            }

            // Handle Driver Assignment (Matching your Filament logic)
            if ($request->has('driver_id')) {
                DB::table('driver_vehicle_assignments')
                    ->where('vehicle_id', $request->vehicle_id)
                    ->whereNull('end_date')
                    ->update(['end_date' => $now]);

                if ($request->driver_id) {
                    $userId = Driver::where('id', $request->driver_id)->value('user_id');

                    DB::table('driver_vehicle_assignments')->insert([
                        'vehicle_id' => $request->vehicle_id,
                        'driver_id'  => $userId,
                        'start_date' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }

            return response()->json(['message' => 'Success']);
        });
    }

    public function export(): BinaryFileResponse
    {
        return Excel::download(new DispatchExport, 'fleet_dispatch_' . now()->format('Y-m-d') . '.xlsx');
    }

    public function history($id)
    {
        $driverHistory = DB::table('driver_vehicle_assignments')
            ->join('users', 'driver_vehicle_assignments.driver_id', '=', 'users.id')
            ->where('vehicle_id', $id)
            ->select('users.name', 'driver_vehicle_assignments.start_date', 'driver_vehicle_assignments.end_date')
            ->orderBy('driver_vehicle_assignments.start_date', 'desc')
            ->limit(5)
            ->get();

        $trailerHistory = DB::table('trailer_assignments')
            ->join('trailers', 'trailer_assignments.trailer_id', '=', 'trailers.id')
            ->where('vehicle_id', $id)
            ->select('trailers.plate_number', 'trailer_assignments.assigned_at', 'trailer_assignments.unassigned_at')
            ->orderBy('trailer_assignments.assigned_at', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'drivers' => $driverHistory,
            'trailers' => $trailerHistory
        ]);
    }
}
