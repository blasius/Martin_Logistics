<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    public function index()
    {
        $currencies = Currency::select('id', 'code', 'name', 'symbol', 'is_default', 'created_at')
            ->orderBy('is_default', 'desc')
            ->orderBy('code')
            ->get();
        return response()->json($currencies);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:3|unique:currencies,code',
            'name' => 'required|string|max:255',
            'symbol' => 'nullable|string|max:5',
            'is_default' => 'boolean',
        ]);

        if ($validated['is_default'] ?? false) {
            Currency::where('is_default', true)->update(['is_default' => false]);
        }

        $currency = Currency::create($validated);

        return response()->json(['message' => 'Currency created successfully', 'currency' => $currency]);
    }

    public function update(Request $request, Currency $currency)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:3|unique:currencies,code,' . $currency->id,
            'name' => 'required|string|max:255',
            'symbol' => 'nullable|string|max:5',
            'is_default' => 'boolean',
        ]);

        if ($validated['is_default'] ?? false) {
            Currency::where('id', '!=', $currency->id)->where('is_default', true)->update(['is_default' => false]);
        }

        $currency->update($validated);

        return response()->json(['message' => 'Currency updated successfully', 'currency' => $currency]);
    }

    public function destroy(Currency $currency)
    {
        $currency->delete();
        return response()->json(['message' => 'Currency deleted successfully']);
    }
}
