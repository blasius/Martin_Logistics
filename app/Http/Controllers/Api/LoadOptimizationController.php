<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Vehicle;
use App\Services\LoadOptimizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoadOptimizationController extends Controller
{
    public function __construct(
        protected LoadOptimizationService $loadOptimizationService
    ) {}

    public function vehicles(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'vehicle_type_id' => 'nullable|exists:vehicle_types,id',
            'min_volume' => 'nullable|numeric|min:0',
            'min_payload' => 'nullable|numeric|min:0',
        ]);

        $vehicles = $this->loadOptimizationService->findAvailableVehicles($validated);
        return response()->json($vehicles);
    }

    public function optimize(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'exists:orders,id',
            'vehicle_ids' => 'nullable|array|min:1',
            'vehicle_ids.*' => 'exists:vehicles,id',
        ]);

        $orders = Order::whereIn('id', $validated['order_ids'])->get();

        $vehicles = collect();
        if ($validated['vehicle_ids'] ?? null) {
            $vehicles = Vehicle::whereIn('id', $validated['vehicle_ids'])
                ->where('status', 'active')
                ->get();
        } else {
            $vehicles = $this->loadOptimizationService->findAvailableVehicles([]);
        }

        $result = $this->loadOptimizationService->consolidateLoads($orders, $vehicles);

        return response()->json($result);
    }

    public function suitability(Request $request, Vehicle $vehicle): JsonResponse
    {
        $validated = $request->validate([
            'weight' => 'required|numeric|min:0',
            'volume' => 'required|numeric|min:0',
        ]);

        $suitable = $this->loadOptimizationService->vehicleSuitability(
            $vehicle,
            $validated['weight'],
            $validated['volume']
        );

        return response()->json([
            'suitable' => $suitable,
            'vehicle' => $vehicle,
            'max_payload' => $vehicle->max_payload,
            'volume_capacity' => $vehicle->volume_capacity,
        ]);
    }
}
