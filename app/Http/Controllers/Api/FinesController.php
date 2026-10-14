<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\CheckPlateJob;
use App\Models\Trailer;
use App\Models\Vehicle;
use App\Models\TrafficFine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Exports\FinesExport;
use Maatwebsite\Excel\Facades\Excel;

class FinesController extends Controller
{
    public function index(Request $request)
    {
        // Ordered by police issue date: a ticket imported today can have been
        // issued months ago, so created_at would shuffle the list by sync run.
        $query = TrafficFine::with(['violations', 'fineable'])
            ->orderByDesc('issued_at')
            ->orderByDesc('id');

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

        // 3. Filter by Status. "outstanding" and "overdue" are derived
        //    states resolved by the model rather than raw status values.
        $status = $request->get('status');

        if ($status === 'outstanding') {
            $query->outstanding();
        } elseif ($status === 'overdue') {
            $query->overdue();
        } elseif ($status === 'due_soon') {
            $query->dueWithin();
        } elseif ($request->filled('status')) {
            $query->where('status', strtoupper($status));
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

            // Date logic — every flag derives from the portal's Pay By date.
            // The app never assumes a payment window length.
            $issuedAt = $fine->issued_at;
            $fine->issued_at_human = $issuedAt ? $issuedAt->diffForHumans() : 'Unknown';

            $fine->due_state = $fine->dueState();
            $fine->due_state_label = $fine->dueStateLabel();
            $fine->days_until_pay_by = $fine->daysUntilPayBy();
            $fine->days_overdue = $fine->daysOverdue();
            $fine->is_overdue = $fine->isOverdue();
            $fine->is_settled = $fine->isSettled();
            $fine->total_due = $fine->total_due;

            return $fine;
        });

        return response()->json($paginator);
    }

    public function recent(string $plate)
    {
        $fines = TrafficFine::with('violations')
            ->where('plate_number', $plate)
            ->orderByDesc('issued_at')
            ->orderByDesc('id')
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

    public function export(Request $request)
    {
        $query = TrafficFine::with(['violations']);

        if ($request->filled('type')) {
            $typeClass = $request->type === 'vehicle' ? Vehicle::class : Trailer::class;
            $query->where('fineable_type', $typeClass);
        }

        if ($request->filled('plate')) {
            $query->where('plate_number', 'like', "%{$request->plate}%");
        }

        // Honour the status filter so the spreadsheet matches what the user is
        // looking at. "outstanding" is the default because the export is a
        // debt report, and its totals only make sense for unpaid tickets.
        $status = $request->get('status');

        if ($status === 'outstanding') {
            $query->outstanding();
        } elseif ($request->filled('status')) {
            $query->where('status', strtoupper($status));
        } else {
            $query->outstanding();
        }

        return Excel::download(new FinesExport($query), 'fleet_debt_report_' . now()->format('Y-m-d_H-i-s_u') . '.xlsx');
    }

    public function stats()
    {
        $outstanding = TrafficFine::outstanding();

        return response()->json([
            // Principal and penalty reported separately so it is clear how much
            // of the debt is late fee; total_due is the sum of the two.
            'total_principal' => (float) (clone $outstanding)->sum('ticket_amount'),
            'total_late_fees' => (float) (clone $outstanding)->sum('late_fee'),
            'total_unpaid' => round(
                (float) (clone $outstanding)->sum('ticket_amount')
                + (float) (clone $outstanding)->sum('late_fee'),
                2
            ),
            'outstanding_count' => (clone $outstanding)->count(),

            // Overdue is driven entirely by the portal Pay By date.
            'overdue_count' => TrafficFine::overdue()->count(),
            'overdue_amount' => round(
                (float) TrafficFine::overdue()->sum('ticket_amount')
                + (float) TrafficFine::overdue()->sum('late_fee'),
                2
            ),
            'due_soon_count' => TrafficFine::dueWithin()->count(),
            'penalized_count' => TrafficFine::penalized()->count(),

            'top_violation' => DB::table('traffic_fine_violations')
                ->join('traffic_fines', 'traffic_fine_violations.traffic_fine_id', '=', 'traffic_fines.id')
                ->whereNotIn('traffic_fines.status', TrafficFine::SETTLED_STATUSES)
                ->select('traffic_fine_violations.violation_name', DB::raw('count(*) as count'))
                ->groupBy('traffic_fine_violations.violation_name')
                ->orderByDesc('count')
                ->first(),
        ]);
    }
}
