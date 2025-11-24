<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\CheckPlateJob;
use App\Models\Trailer;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use App\Models\TrafficFine;

class FinesController extends Controller
{
    // GET /api/portal/fines
    public function index(Request $request)
    {
        $query = TrafficFine::with(['violations','fineable'])->orderByDesc('created_at');

        if ($request->filled('type')) {
            if ($request->type === 'vehicle') {
                $query->where('fineable_type', Vehicle::class);
            } elseif ($request->type === 'trailer') {
                $query->where('fineable_type', Trailer::class);
            }
        }

        if ($request->filled('plate')) {
            $q = $request->plate;
            $query->where('plate_number','like',"%{$q}%");
        }

        if ($request->filled('status')) {
            $query->where('status', strtoupper($request->status));
        }

        $perPage = intval($request->get('per_page', 15));
        $page = intval($request->get('page', 1));

        return response()->json($query->paginate($perPage, ['*'], 'page', $page));
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
