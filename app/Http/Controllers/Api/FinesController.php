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

        // 1. Filter by Type
        if ($request->filled('type')) {
            if ($request->type === 'vehicle') {
                $query->where('fineable_type', Vehicle::class);
            } elseif ($request->type === 'trailer') {
                $query->where('fineable_type', Trailer::class);
            }
        }

        // 2. Search by Plate OR Driver Name (Manual SQL Subqueries)
        if ($request->filled('plate')) {
            $searchTerm = $request->plate;

            $query->where(function ($q) use ($searchTerm) {
                // Search by Plate Number
                $q->where('plate_number', 'like', "%{$searchTerm}%");

                // Search by Driver Name for Vehicles
                $q->orWhere(function ($sub) use ($searchTerm) {
                    $sub->where('fineable_type', Vehicle::class)
                        ->whereIn('fineable_id', function ($dbQuery) use ($searchTerm) {
                            $dbQuery->select('vehicle_id')
                                ->from('driver_vehicle_assignments')
                                ->join('users', 'users.id', '=', 'driver_vehicle_assignments.driver_id')
                                ->where('users.name', 'like', "%{$searchTerm}%")
                                ->whereNull('end_date');
                        });
                });

                // Search by Driver Name for Trailers
                $q->orWhere(function ($sub) use ($searchTerm) {
                    $sub->where('fineable_type', Trailer::class)
                        ->whereIn('fineable_id', function ($dbQuery) use ($searchTerm) {
                            $dbQuery->select('trailer_id')
                                ->from('trailer_assignments')
                                ->join('driver_vehicle_assignments', 'trailer_assignments.vehicle_id', '=', 'driver_vehicle_assignments.vehicle_id')
                                ->join('users', 'users.id', '=', 'driver_vehicle_assignments.driver_id')
                                ->where('users.name', 'like', "%{$searchTerm}%")
                                ->whereNull('trailer_assignments.unassigned_at')
                                ->whereNull('driver_vehicle_assignments.end_date');
                        });
                });
            });
        }

        // 3. Filter by Status
        if ($request->filled('status')) {
            $query->where('status', strtoupper($request->status));
        }

        $perPage = intval($request->get('per_page', 15));
        $paginator = $query->paginate($perPage);

        // 4. Attach metadata for Vue (Driver Name & Human Dates)
        $paginator->getCollection()->transform(function ($fine) {
            $driverName = null;

            if ($fine->fineable_type === Vehicle::class) {
                $driverName = DB::table('users')
                    ->join('driver_vehicle_assignments', 'users.id', '=', 'driver_vehicle_assignments.driver_id')
                    ->where('driver_vehicle_assignments.vehicle_id', $fine->fineable_id)
                    ->whereNull('driver_vehicle_assignments.end_date')
                    ->value('users.name');
            } elseif ($fine->fineable_type === Trailer::class) {
                $driverName = DB::table('users')
                    ->join('driver_vehicle_assignments', 'users.id', '=', 'driver_vehicle_assignments.driver_id')
                    ->join('trailer_assignments', 'driver_vehicle_assignments.vehicle_id', '=', 'trailer_assignments.vehicle_id')
                    ->where('trailer_assignments.trailer_id', $fine->fineable_id)
                    ->whereNull('driver_vehicle_assignments.end_date')
                    ->whereNull('trailer_assignments.unassigned_at')
                    ->value('users.name');
            }

            $fine->assigned_driver_name = $driverName;

            // Date logic
            $issuedAt = $fine->issued_at ? Carbon::parse($fine->issued_at) : null;
            $fine->issued_at_human = $issuedAt ? $issuedAt->diffForHumans() : 'Unknown';
            $fine->is_overdue = $issuedAt ? $issuedAt->diffInDays(now()) > 14 : false;
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
