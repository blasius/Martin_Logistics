<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Services\ContractService;
use Illuminate\Http\Request;

class ContractController extends Controller
{
    public function __construct(protected ContractService $contractService) {}

    public function index(Request $request)
    {
        $query = Contract::with(['client:id,user_id', 'client.user:id,name', 'rateCard:id,name']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        return $query->orderBy('created_at', 'desc')->paginate($request->per_page ?? 50);
    }

    public function show(Contract $contract)
    {
        return $contract->load(['client:id,user_id', 'client.user:id,name', 'rateCard:id,name,items', 'documents']);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'type' => 'required|in:monthly,yearly,spot',
            'rate_card_id' => 'nullable|exists:rate_cards,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'sometimes|in:draft,active,expired,cancelled',
            'terms' => 'nullable|string',
            'sla_response_hours' => 'nullable|integer|min:1',
            'sla_resolution_hours' => 'nullable|integer|min:1',
            'auto_renew' => 'boolean',
        ]);

        $validated['reference'] = $this->contractService->generateReference();

        return Contract::create($validated)->load(['client:id,user_id', 'client.user:id,name', 'rateCard:id,name']);
    }

    public function update(Request $request, Contract $contract)
    {
        $validated = $request->validate([
            'client_id' => 'sometimes|exists:clients,id',
            'type' => 'sometimes|in:monthly,yearly,spot',
            'rate_card_id' => 'nullable|exists:rate_cards,id',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'status' => 'sometimes|in:draft,active,expired,cancelled',
            'terms' => 'nullable|string',
            'sla_response_hours' => 'nullable|integer|min:1',
            'sla_resolution_hours' => 'nullable|integer|min:1',
            'auto_renew' => 'sometimes|boolean',
        ]);

        $contract->update($validated);

        return $contract->fresh()->load(['client:id,user_id', 'client.user:id,name', 'rateCard:id,name']);
    }

    public function destroy(Contract $contract)
    {
        $contract->delete();
        return response()->json(['message' => 'Contract deleted']);
    }

    public function expiryWarnings()
    {
        return response()->json($this->contractService->checkExpiry());
    }

    public function slaStatus()
    {
        return response()->json($this->contractService->checkSla());
    }
}
