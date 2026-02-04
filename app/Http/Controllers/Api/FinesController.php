<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\CheckPlateJob;
use App\Models\Trailer;
use App\Models\Vehicle;
use App\Models\TrafficFine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FinesController extends Controller
{
    public function index(Request $request)
    {
        $query = TrafficFine::with(['violations', 'fineable'])->orderByDesc('created_at');

        // ... existing filters (type, plate, status) ...
        if ($request->filled('type')) {
            if ($request->type === 'vehicle') { $query->where('fineable_type', Vehicle::class); }
            elseif ($request->type === 'trailer') { $query->where('fineable_type', Trailer::class); }
        }
        if ($request->filled('plate')) {
            $query->where('plate_number', 'like', "%{$request->plate}%");
        }
        if ($request->filled('status')) {
            $query->where('status', strtoupper($request->status));
        }

        $perPage = intval($request->get('per_page', 15));
        $paginator = $query->paginate($perPage);

        $paginator->getCollection()->transform(function ($fine) {
            // 1. Driver Logic (Same as before)
            $driverName = null;
            if ($fine->fineable_type === Vehicle::class && $fine->fineable_id) {
                $driverName = DB::table('users')
                    ->join('driver_vehicle_assignments', 'users.id', '=', 'driver_vehicle_assignments.driver_id')
                    ->where('driver_vehicle_assignments.vehicle_id', $fine->fineable_id)
                    ->whereNull('driver_vehicle_assignments.end_date')
                    ->value('users.name');
            } elseif ($fine->fineable_type === Trailer::class && $fine->fineable_id) {
                $driverName = DB::table('users')
                    ->join('driver_vehicle_assignments', 'users.id', '=', 'driver_vehicle_assignments.driver_id')
                    ->join('trailer_assignments', 'driver_vehicle_assignments.vehicle_id', '=', 'trailer_assignments.vehicle_id')
                    ->where('trailer_assignments.trailer_id', $fine->fineable_id)
                    ->whereNull('driver_vehicle_assignments.end_date')
                    ->whereNull('trailer_assignments.unassigned_at')
                    ->value('users.name');
            }

            // 2. Date & Penalty Logic
            $issuedAt = $fine->issued_at ? Carbon::parse($fine->issued_at) : null;

            $fine->assigned_driver_name = $driverName;
            $fine->issued_at_human = $issuedAt ? $issuedAt->diffForHumans() : 'Unknown date';

            // Check if older than 14 days and unpaid
            $fine->is_overdue = $issuedAt ? $issuedAt->diffInDays(Carbon::now()) > 14 : false;
            $fine->show_penalty_warning = ($fine->is_overdue && $fine->status === 'PENDING');

            return $fine;
        });

        return response()->json($paginator);
    }

    public function recent(string $plate)
    {
        $fines = TrafficFine::with('violations')
            ->where('plate_number', $plate)
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json($fines);
    }

    public function forceCheck(Request $request)
    {
        $data = $request->validate([
            'plate' => 'required|string',
            'type' => 'required|in:vehicle,trailer',
        ]);

        CheckPlateJob::dispatch($data['plate'], $data['type']);

        return response()->json(['status' => 'queued']);
    }
}
