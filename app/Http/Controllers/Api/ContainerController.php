<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Container;
use App\Models\ContainerMovement;
use App\Models\ContainerPenalty;
use App\Models\ShippingLineContract;
use App\Services\ContainerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContainerController extends Controller
{
    public function __construct(protected ContainerService $containerService) {}

    public function index(Request $request): JsonResponse
    {
        $query = Container::with(['contract', 'penalties']);

        if ($request->filled('search')) {
            $query->where('container_id', 'like', "%{$request->search}%");
        }

        if ($request->filled('status')) {
            $query->where('current_status', $request->status);
        }

        if ($request->filled('owner_type')) {
            $query->where('owner_type', $request->owner_type);
        }

        if ($request->filled('size')) {
            $query->where('size', $request->size);
        }

        $containers = $query->latest()->paginate($request->per_page ?? 25);

        $containers->getCollection()->transform(function ($c) {
            return [
                'id' => $c->id,
                'container_id' => $c->container_id,
                'size' => $c->size,
                'type' => $c->type,
                'owner_type' => $c->owner_type,
                'is_owned' => $c->is_owned,
                'current_status' => $c->current_status,
                'shipping_line' => $c->contract?->shipping_line,
                'has_penalties' => $c->penalties->where('days_overdue', '>', 0)->isNotEmpty(),
                'total_penalty' => $c->penalties->sum('total_amount'),
                'is_active' => $c->is_active,
            ];
        });

        return response()->json($containers);
    }

    public function show(int $id): JsonResponse
    {
        $container = Container::with(['contract.currency', 'currentLocation', 'movements.vehicle', 'movements.driver', 'movements.trip', 'penalties'])
            ->find($id);

        if (!$container) {
            return response()->json(['message' => 'Container not found'], 404);
        }

        return response()->json([
            'data' => [
                'id' => $container->id,
                'container_id' => $container->container_id,
                'size' => $container->size,
                'type' => $container->type,
                'owner_type' => $container->owner_type,
                'is_owned' => $container->is_owned,
                'purchase_value' => $container->purchase_value,
                'purchase_date' => $container->purchase_date,
                'current_status' => $container->current_status,
                'current_location' => $container->currentLocation ? [
                    'id' => $container->currentLocation->id,
                    'name' => $container->currentLocation->name ?? $container->currentLocation->container_id,
                    'type' => class_basename($container->currentLocation::class),
                ] : null,
                'last_known_gps' => $container->last_known_gps,
                'is_active' => $container->is_active,
                'shipping_line' => $container->contract?->shipping_line,
                'free_demurrage_days' => $container->contract?->free_demurrage_days,
                'free_detention_days' => $container->contract?->free_detention_days,
                'demurrage_tiers' => $container->contract?->demurrage_tiers,
                'detention_tiers' => $container->contract?->detention_tiers,
                'movements' => $container->movements->map(fn($m) => [
                    'id' => $m->id,
                    'movement_type' => $m->movement_type,
                    'vehicle' => $m->vehicle?->plate_number ?? $m->vehicle?->id,
                    'driver' => $m->driver?->name,
                    'trip_reference' => $m->trip?->reference,
                    'seal_number' => $m->seal_number,
                    'departed_at' => $m->departed_at,
                    'arrived_at' => $m->arrived_at,
                    'notes' => $m->notes,
                ]),
                'penalties' => $container->penalties->map(fn($p) => [
                    'id' => $p->id,
                    'type' => $p->penalty_type,
                    'days_overdue' => $p->days_overdue,
                    'daily_rate' => $p->daily_rate,
                    'total_amount' => $p->total_amount,
                    'calculated_at' => $p->calculated_at,
                ]),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'container_id' => 'required|string|max:50|unique:containers,container_id',
            'size' => 'required|string|in:20ft,40ft,40hc',
            'type' => 'required|string|in:dry,reefer,open_top,flat_rack,tank',
            'owner_type' => 'required|string|in:private,shipping_line',
            'shipping_line_contract_id' => 'nullable|exists:shipping_line_contracts,id',
            'is_owned' => 'boolean',
            'purchase_value' => 'nullable|numeric|min:0',
            'purchase_date' => 'nullable|date',
            'current_status' => 'required|string|in:at_port,in_transit,at_warehouse,at_customer,empty_returned,damaged,scrapped',
            'current_location_type' => 'nullable|string',
            'current_location_id' => 'nullable|integer',
            'last_known_gps' => 'nullable|string|max:255',
        ]);

        $container = Container::create($validated);

        return response()->json([
            'message' => 'Container created',
            'data' => $container,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $container = Container::find($id);
        if (!$container) {
            return response()->json(['message' => 'Container not found'], 404);
        }

        $validated = $request->validate([
            'size' => 'sometimes|string|in:20ft,40ft,40hc',
            'type' => 'sometimes|string|in:dry,reefer,open_top,flat_rack,tank',
            'owner_type' => 'sometimes|string|in:private,shipping_line',
            'shipping_line_contract_id' => 'nullable|exists:shipping_line_contracts,id',
            'is_owned' => 'boolean',
            'purchase_value' => 'nullable|numeric|min:0',
            'purchase_date' => 'nullable|date',
            'current_status' => 'sometimes|string|in:at_port,in_transit,at_warehouse,at_customer,empty_returned,damaged,scrapped',
            'current_location_type' => 'nullable|string',
            'current_location_id' => 'nullable|integer',
            'last_known_gps' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $container->update($validated);

        return response()->json([
            'message' => 'Container updated',
            'data' => $container->fresh(),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $container = Container::find($id);
        if (!$container) {
            return response()->json(['message' => 'Container not found'], 404);
        }

        $container->delete();

        return response()->json(['message' => 'Container deleted']);
    }

    public function dashboard(): JsonResponse
    {
        $stats = $this->containerService->dashboardStats();
        $overdue = $this->containerService->overdueContainers();

        return response()->json([
            'data' => [
                'stats' => $stats,
                'overdue_containers' => $overdue,
            ],
        ]);
    }

    public function movements(int $id): JsonResponse
    {
        $container = Container::find($id);
        if (!$container) {
            return response()->json(['message' => 'Container not found'], 404);
        }

        $movements = ContainerMovement::where('container_id', $id)
            ->with(['vehicle', 'driver', 'trip'])
            ->orderByDesc('created_at')
            ->paginate(25);

        return response()->json($movements);
    }

    public function recordMovement(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'container_id' => 'required|exists:containers,id',
            'from_location_type' => 'nullable|string',
            'from_location_id' => 'nullable|integer',
            'to_location_type' => 'required|string',
            'to_location_id' => 'required|integer',
            'movement_type' => 'required|string|in:port_pickup,delivery_to_customer,return_to_depot,reposition,transfer',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'driver_id' => 'nullable|exists:drivers,id',
            'trip_id' => 'nullable|exists:trips,id',
            'seal_number' => 'nullable|string|max:100',
            'departed_at' => 'nullable|date',
            'arrived_at' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        $movement = $this->containerService->recordMovement($validated);

        return response()->json([
            'message' => 'Movement recorded',
            'data' => $movement->load(['vehicle', 'driver', 'trip']),
        ], 201);
    }

    public function calculatePenalties(int $id): JsonResponse
    {
        $container = Container::find($id);
        if (!$container) {
            return response()->json(['message' => 'Container not found'], 404);
        }

        $results = $this->containerService->calculatePenalties($container);

        return response()->json([
            'message' => 'Penalties calculated',
            'data' => $results,
        ]);
    }

    public function batchCalculatePenalties(): JsonResponse
    {
        $count = $this->containerService->batchCalculateAllPenalties();

        return response()->json([
            'message' => "Penalties calculated for {$count} containers",
            'count' => $count,
        ]);
    }

    public function contracts(): JsonResponse
    {
        $contracts = ShippingLineContract::with('currency')
            ->where('is_active', true)
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'shipping_line' => $c->shipping_line,
                'name' => $c->name,
                'free_demurrage_days' => $c->free_demurrage_days,
                'free_detention_days' => $c->free_detention_days,
                'demurrage_tiers' => $c->demurrage_tiers,
                'detention_tiers' => $c->detention_tiers,
                'currency' => $c->currency?->code,
                'effective_from' => $c->effective_from,
                'effective_to' => $c->effective_to,
            ]);

        return response()->json(['data' => $contracts]);
    }

    public function storeContract(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shipping_line' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'free_demurrage_days' => 'required|integer|min:0',
            'free_detention_days' => 'required|integer|min:0',
            'demurrage_tiers' => 'nullable|array',
            'demurrage_tiers.*.days_from' => 'required|integer|min:0',
            'demurrage_tiers.*.days_to' => 'required|integer|min:0',
            'demurrage_tiers.*.daily_rate' => 'required|numeric|min:0',
            'detention_tiers' => 'nullable|array',
            'detention_tiers.*.days_from' => 'required|integer|min:0',
            'detention_tiers.*.days_to' => 'required|integer|min:0',
            'detention_tiers.*.daily_rate' => 'required|numeric|min:0',
            'currency_id' => 'required|exists:currencies,id',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after:effective_from',
        ]);

        $contract = ShippingLineContract::create($validated);

        return response()->json([
            'message' => 'Contract created',
            'data' => $contract->load('currency'),
        ], 201);
    }

    public function searchLocations(Request $request): JsonResponse
    {
        $request->validate(['q' => 'required|string|min:1']);
        $results = $this->containerService->searchLocations($request->q);
        return response()->json(['data' => $results]);
    }
}
