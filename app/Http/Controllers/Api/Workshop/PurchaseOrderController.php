<?php

namespace App\Http\Controllers\Api\Workshop;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\PoItem;
use App\Services\PurchaseOrderService;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    public function __construct(
        protected PurchaseOrderService $poService
    ) {}

    public function index(Request $request)
    {
        $query = PurchaseOrder::with(['vendor:id,name', 'repairRequest:id,reference', 'currency:id,code']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->vendor_id) {
            $query->where('vendor_id', $request->vendor_id);
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('reference', 'like', "%{$request->search}%")
                    ->orWhereHas('vendor', fn($v) => $v->where('name', 'like', "%{$request->search}%"));
            });
        }

        $query->orderByRaw("FIELD(status, 'draft', 'sent', 'confirmed', 'partially_received', 'received', 'cancelled')");

        return response()->json([
            'purchase_orders' => $query->paginate($request->per_page ?? 15),
            'stats' => [
                'total' => PurchaseOrder::count(),
                'draft' => PurchaseOrder::where('status', 'draft')->count(),
                'sent' => PurchaseOrder::where('status', 'sent')->count(),
                'received' => PurchaseOrder::whereIn('status', ['received', 'partially_received'])->count(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'repair_request_id' => 'nullable|exists:repair_requests,id',
            'expected_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'currency_id' => 'nullable|exists:currencies,id',
            'items' => 'required|array|min:1',
            'items.*.part_id' => 'nullable|exists:parts,id',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $po = $this->poService->create($validated, $validated['items']);

        return $po->load(['vendor:id,name', 'items.part:id,name,sku', 'currency:id,code']);
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        return $purchaseOrder->load([
            'vendor',
            'items.part:id,name,sku',
            'repairRequest:id,reference',
            'currency:id,code',
        ]);
    }

    public function send(PurchaseOrder $purchaseOrder)
    {
        $po = $this->poService->send($purchaseOrder);

        return $po->load(['vendor:id,name', 'items.part:id,name,sku', 'currency:id,code']);
    }

    public function confirm(PurchaseOrder $purchaseOrder)
    {
        $po = $this->poService->confirm($purchaseOrder);

        return $po->load(['vendor:id,name', 'items.part:id,name,sku', 'currency:id,code']);
    }

    public function receive(Request $request, PurchaseOrder $purchaseOrder)
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'items' => 'required|array|min:1',
            'items.*.po_item_id' => 'required|exists:po_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        $po = $this->poService->receive($purchaseOrder, $validated['items'], $validated['warehouse_id'], $request->user()->id);

        return $po->load(['vendor:id,name', 'items.part:id,name,sku', 'currency:id,code']);
    }

    public function cancel(PurchaseOrder $purchaseOrder)
    {
        $po = $this->poService->cancel($purchaseOrder);

        return $po->load(['vendor:id,name', 'items.part:id,name,sku', 'currency:id,code']);
    }
}
