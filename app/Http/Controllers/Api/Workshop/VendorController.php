<?php

namespace App\Http\Controllers\Api\Workshop;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        $query = Vendor::query();

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('contact', 'like', "%{$request->search}%")
                    ->orWhere('tin', 'like', "%{$request->search}%");
            });
        }

        return response()->json([
            'vendors' => $query->orderBy('name')->paginate($request->per_page ?? 15),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'contact' => 'nullable|string',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'tin' => 'nullable|string',
            'payment_terms' => 'nullable|string',
            'supply_categories' => 'nullable|string',
        ]);

        return Vendor::create($validated);
    }

    public function show(Vendor $vendor)
    {
        return $vendor->load('purchaseOrders');
    }

    public function update(Request $request, Vendor $vendor)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'contact' => 'nullable|string',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'tin' => 'nullable|string',
            'payment_terms' => 'nullable|string',
            'supply_categories' => 'nullable|string',
        ]);

        $vendor->update($validated);

        return $vendor;
    }

    public function destroy(Vendor $vendor)
    {
        $vendor->delete();

        return response()->json(['message' => 'Vendor deleted']);
    }

    public function list()
    {
        return Vendor::orderBy('name')->get(['id', 'name', 'tin']);
    }
}
