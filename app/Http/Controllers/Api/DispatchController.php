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
use Illuminate\Support\Facades\URL;
use Barryvdh\DomPDF\Facade\Pdf;

class DispatchController extends Controller
{
    public function index()
    {
        $vehicles = Vehicle::with(['currentAssignment.trailer'])->get()->map(function ($vehicle) {
            $currentDriver = DB::table('users')
                ->join('driver_vehicle_assignments', 'users.id', '=', 'driver_vehicle_assignments.driver_id')
                ->where('driver_vehicle_assignments.vehicle_id', $vehicle->id)
                ->whereNull('driver_vehicle_assignments.end_date')
                // Added start_date for the "Last Updated" badge
                ->select('users.id', 'users.name', 'driver_vehicle_assignments.start_date')
                ->first();

            $vehicle->current_driver = $currentDriver;
            return $vehicle;
        });

        $allDrivers = Driver::with('user')->get();
        $allTrailers = Trailer::where('status', 'active')->get();

        $canEdit = auth()->user()->roles()
            ->whereIn('name', ['admin', 'super_admin'])
            ->exists();

        return response()->json([
            'vehicles' => $vehicles,
            'available_drivers' => $allDrivers,
            'available_trailers' => $allTrailers,
            'can_edit' => $canEdit
        ]);
    }

    public function pair(Request $request)
    {
        $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'driver_id'  => 'nullable',
            'trailer_id' => 'nullable',
        ]);

        return DB::transaction(function () use ($request) {
            $now = now();

            if ($request->has('trailer_id')) {
                DB::table('trailer_assignments')
                    ->where('vehicle_id', $request->vehicle_id)
                    ->whereNull('unassigned_at')
                    ->update(['unassigned_at' => $now]);

                if ($request->trailer_id && $request->trailer_id !== 'null') {
                    DB::table('trailer_assignments')
                        ->where('trailer_id', $request->trailer_id)
                        ->whereNull('unassigned_at')
                        ->update(['unassigned_at' => $now]);

                    DB::table('trailer_assignments')->insert([
                        'vehicle_id'  => $request->vehicle_id,
                        'trailer_id'  => $request->trailer_id,
                        'assigned_at' => $now,
                        'created_at'  => $now,
                        'updated_at'  => $now,
                    ]);
                }
            }

            if ($request->has('driver_id')) {
                DB::table('driver_vehicle_assignments')
                    ->where('vehicle_id', $request->vehicle_id)
                    ->whereNull('end_date')
                    ->update(['end_date' => $now]);

                if ($request->driver_id && $request->driver_id !== 'null') {
                    $userId = Driver::where('id', $request->driver_id)->value('user_id') ?? $request->driver_id;

                    DB::table('driver_vehicle_assignments')
                        ->where('driver_id', $userId)
                        ->whereNull('end_date')
                        ->update(['end_date' => $now]);

                    DB::table('driver_vehicle_assignments')->insert([
                        'vehicle_id' => $request->vehicle_id,
                        'driver_id'  => $userId,
                        'start_date' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
            return response()->json(['message' => 'Dispatch status updated successfully.']);
        });
    }

    public function toggleMaintenance(Request $request)
    {
        $request->validate(['vehicle_id' => 'required']);
        $vehicleId = $request->vehicle_id;

        DB::transaction(function () use ($vehicleId) {
            DB::table('vehicles')->where('id', $vehicleId)->update(['status' => 'maintenance']);
            DB::table('driver_vehicle_assignments')->where('vehicle_id', $vehicleId)->whereNull('end_date')->update(['end_date' => now()]);
            DB::table('trailer_assignments')->where('vehicle_id', $vehicleId)->whereNull('unassigned_at')->update(['unassigned_at' => now()]);
        });

        return response()->json(['message' => 'Vehicle moved to maintenance.']);
    }

    public function activateVehicle(Request $request)
    {
        $request->validate(['vehicle_id' => 'required']);
        DB::table('vehicles')->where('id', $request->vehicle_id)->update(['status' => 'active']);
        return response()->json(['message' => 'Vehicle returned to active service.']);
    }

    public function history($id)
    {
        $drivers = DB::table('driver_vehicle_assignments')
            ->join('users', 'driver_vehicle_assignments.driver_id', '=', 'users.id')
            ->where('vehicle_id', $id)
            ->select('users.name', 'driver_vehicle_assignments.start_date', 'driver_vehicle_assignments.end_date')
            ->orderBy('start_date', 'desc')->limit(5)->get();

        $trailers = DB::table('trailer_assignments')
            ->join('trailers', 'trailer_assignments.trailer_id', '=', 'trailers.id')
            ->where('vehicle_id', $id)
            ->select('trailers.plate_number', 'trailer_assignments.assigned_at', 'trailer_assignments.unassigned_at')
            ->orderBy('assigned_at', 'desc')->limit(5)->get();

        return response()->json(['drivers' => $drivers, 'trailers' => $trailers]);
    }

    public function getPrintUrl()
    {
        return response()->json([
            'url' => URL::temporarySignedRoute('dispatch.print.secure', now()->addMinutes(10))
        ]);
    }

    public function printStatus(Request $request)
    {
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

        $pdf = Pdf::loadView('pdfs.dispatch_board', compact('vehicles'))->setPaper('a4', 'landscape');
        return $pdf->stream('fleet-status.pdf');
    }

    public function export(): BinaryFileResponse
    {
        return Excel::download(new DispatchExport, 'fleet_dispatch_' . now()->format('Y-m-d') . '.xlsx');
    }
}
