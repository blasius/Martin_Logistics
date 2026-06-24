<?php

namespace App\Http\Controllers\Api\Workshop;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class AvailablePoolController extends Controller
{
    public function index(Request $request)
    {
        $query = Vehicle::where('status', 'released_from_workshop')
            ->with(['repairRequests' => function ($q) {
                $q->latest()->limit(1);
            }]);

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('plate_number', 'like', "%{$request->search}%")
                    ->orWhere('make', 'like', "%{$request->search}%")
                    ->orWhere('model', 'like', "%{$request->search}%");
            });
        }

        return $query->orderBy('updated_at', 'desc')
            ->paginate($request->per_page ?? 15);
    }

    public function show(Vehicle $vehicle)
    {
        abort_unless($vehicle->status === 'released_from_workshop', 404, 'Vehicle is not in the available pool.');

        return $vehicle->load([
            'repairRequests' => function ($q) {
                $q->latest()->limit(1);
            },
            'repairRequests.release.releasedBy:id,name',
        ]);
    }
}
