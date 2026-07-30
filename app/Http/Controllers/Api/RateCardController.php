<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RateCard;
use App\Models\RateCalculatorRequest;
use App\Services\RateCalculatorService;
use Illuminate\Http\Request;

class RateCardController extends Controller
{
    public function __construct(protected RateCalculatorService $rateService) {}

    public function index(Request $request)
    {
        $query = RateCard::with(['client:id,user_id', 'client.user:id,name', 'items.currency:id,code,symbol']);

        if ($request->boolean('active_only')) {
            $query->where('is_active', true);
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        return $query->orderBy('created_at', 'desc')->paginate($request->per_page ?? 50);
    }

    public function show(RateCard $rateCard)
    {
        return $rateCard->load(['client:id,user_id', 'client.user:id,name', 'items.currency:id,code,symbol']);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'client_id' => 'nullable|exists:clients,id',
            'is_active' => 'boolean',
            'items' => 'required|array|min:1',
            'items.*.charge_name' => 'required|string|max:255',
            'items.*.charge_type' => 'required|in:per_km,per_kg,per_container,flat,per_stop,fuel_surcharge',
            'items.*.amount' => 'required|numeric|min:0',
            'items.*.currency_id' => 'required|exists:currencies,id',
            'items.*.min_charge' => 'nullable|numeric|min:0',
            'items.*.max_charge' => 'nullable|numeric|min:0',
        ]);

        $card = \DB::transaction(function () use ($validated) {
            $card = RateCard::create([
                'name' => $validated['name'],
                'effective_from' => $validated['effective_from'],
                'effective_to' => $validated['effective_to'] ?? null,
                'client_id' => $validated['client_id'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            foreach ($validated['items'] as $item) {
                $card->items()->create($item);
            }

            return $card;
        });

        return $card->fresh()->load(['client:id,user_id', 'client.user:id,name', 'items.currency:id,code,symbol']);
    }

    public function update(Request $request, RateCard $rateCard)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'effective_from' => 'sometimes|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'client_id' => 'nullable|exists:clients,id',
            'is_active' => 'sometimes|boolean',
            'items' => 'sometimes|array|min:1',
            'items.*.id' => 'nullable|exists:rate_card_items,id',
            'items.*.charge_name' => 'required_with:items|string|max:255',
            'items.*.charge_type' => 'required_with:items|in:per_km,per_kg,per_container,flat,per_stop,fuel_surcharge',
            'items.*.amount' => 'required_with:items|numeric|min:0',
            'items.*.currency_id' => 'required_with:items|exists:currencies,id',
            'items.*.min_charge' => 'nullable|numeric|min:0',
            'items.*.max_charge' => 'nullable|numeric|min:0',
        ]);

        $card = \DB::transaction(function () use ($validated, $rateCard) {
            $rateCard->update($validated);

            if (isset($validated['items'])) {
                $incomingIds = collect($validated['items'])->pluck('id')->filter();
                $rateCard->items()->whereNotIn('id', $incomingIds)->delete();

                foreach ($validated['items'] as $item) {
                    if (isset($item['id'])) {
                        $rateCard->items()->where('id', $item['id'])->update($item);
                    } else {
                        $rateCard->items()->create($item);
                    }
                }
            }

            return $rateCard;
        });

        return $card->fresh()->load(['client:id,user_id', 'client.user:id,name', 'items.currency:id,code,symbol']);
    }

    public function destroy(RateCard $rateCard)
    {
        $rateCard->delete();
        return response()->json(['message' => 'Rate card deleted']);
    }

    public function calculate(Request $request)
    {
        $validated = $request->validate([
            'rate_card_id' => 'nullable|exists:rate_cards,id',
            'client_id' => 'nullable|exists:clients,id',
            'distance_km' => 'nullable|numeric|min:0',
            'weight_kg' => 'nullable|numeric|min:0',
            'container_count' => 'nullable|integer|min:1',
            'stop_count' => 'nullable|integer|min:0',
        ]);

        $calcRequest = new RateCalculatorRequest(
            distanceKm: $validated['distance_km'] ?? null,
            weightKg: $validated['weight_kg'] ?? null,
            containerCount: $validated['container_count'] ?? null,
            stopCount: $validated['stop_count'] ?? null,
            rateCardId: $validated['rate_card_id'] ?? null,
            clientId: $validated['client_id'] ?? null,
        );

        return response()->json($this->rateService->calculate($calcRequest));
    }

    public function preview(Request $request)
    {
        $validated = $request->validate([
            'rate_card_id' => 'required|exists:rate_cards,id',
            'distance_km' => 'required|numeric|min:0',
            'weight_kg' => 'nullable|numeric|min:0',
            'container_count' => 'nullable|integer|min:1',
            'stop_count' => 'nullable|integer|min:0',
        ]);

        $calcRequest = new RateCalculatorRequest(
            distanceKm: $validated['distance_km'],
            weightKg: $validated['weight_kg'] ?? null,
            containerCount: $validated['container_count'] ?? null,
            stopCount: $validated['stop_count'] ?? null,
            rateCardId: $validated['rate_card_id'],
        );

        return response()->json($this->rateService->calculate($calcRequest));
    }
}
