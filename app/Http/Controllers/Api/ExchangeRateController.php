<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExchangeRate;
use Illuminate\Http\Request;

class ExchangeRateController extends Controller
{
    public function index()
    {
        $rates = ExchangeRate::with(['baseCurrency:id,code,name,symbol', 'targetCurrency:id,code,name,symbol', 'creator:id,name'])
            ->orderBy('valid_from', 'desc')
            ->get();

        return response()->json($rates);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'base_currency_id' => 'required|exists:currencies,id',
            'target_currency_id' => 'required|exists:currencies,id',
            'rate' => 'required|numeric|min:0.000001',
            'valid_from' => 'required|date',
            'valid_to' => 'nullable|date|after:valid_from',
        ]);

        $rate = new ExchangeRate();
        $rate->base_currency_id = $validated['base_currency_id'];
        $rate->target_currency_id = $validated['target_currency_id'];
        $rate->base_currency = \App\Models\Currency::find($validated['base_currency_id'])->code;
        $rate->target_currency = \App\Models\Currency::find($validated['target_currency_id'])->code;
        $rate->rate = $validated['rate'];
        $rate->valid_from = $validated['valid_from'];
        $rate->valid_to = $validated['valid_to'] ?? null;
        $rate->created_by = $request->user()->id;
        $rate->save();

        $rate->load(['baseCurrency:id,code,name,symbol', 'targetCurrency:id,code,name,symbol', 'creator:id,name']);

        return response()->json(['message' => 'Exchange rate created', 'rate' => $rate]);
    }

    public function update(Request $request, ExchangeRate $exchangeRate)
    {
        $validated = $request->validate([
            'rate' => 'sometimes|numeric|min:0.000001',
            'valid_from' => 'sometimes|date',
            'valid_to' => 'nullable|date|after:valid_from',
        ]);

        $exchangeRate->update($validated);

        $exchangeRate->load(['baseCurrency:id,code,name,symbol', 'targetCurrency:id,code,name,symbol', 'creator:id,name']);

        return response()->json(['message' => 'Exchange rate updated', 'rate' => $exchangeRate]);
    }

    public function destroy(ExchangeRate $exchangeRate)
    {
        $exchangeRate->delete();
        return response()->json(['message' => 'Exchange rate deleted']);
    }
}
