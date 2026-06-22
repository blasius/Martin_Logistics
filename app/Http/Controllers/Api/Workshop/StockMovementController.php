<?php

namespace App\Http\Controllers\Api\Workshop;

use App\Http\Controllers\Controller;
use App\Models\StockMovement;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    public function index(Request $request)
    {
        $query = StockMovement::with([
            'warehouse:id,name',
            'part:id,name,sku',
            'user:id,name',
        ]);

        if ($request->warehouse_id) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->part_id) {
            $query->where('part_id', $request->part_id);
        }

        if ($request->type) {
            $query->where('type', $request->type);
        }

        return response()->json([
            'movements' => $query->latest()->paginate($request->per_page ?? 20),
        ]);
    }
}
