<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with('client:id,name')
            ->select('id', 'client_id', 'reference', 'origin', 'destination', 'pickup_date', 'status', 'price', 'created_at')
            ->orderByDesc('created_at')
            ->get();
        return response()->json($orders);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'origin' => 'required|string|max:255',
            'destination' => 'required|string|max:255',
            'pickup_date' => 'nullable|date',
            'status' => 'required|in:draft,confirmed,in_transit,delivered,cancelled',
            'price' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $order = Order::create($validated);

        return response()->json(['message' => 'Order created successfully', 'order' => $order->load('client:id,name')]);
    }

    public function show(Order $order)
    {
        return response()->json($order->load('client:id,name'));
    }

    public function update(Request $request, Order $order)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'origin' => 'required|string|max:255',
            'destination' => 'required|string|max:255',
            'pickup_date' => 'nullable|date',
            'status' => 'required|in:draft,confirmed,in_transit,delivered,cancelled',
            'price' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $order->update($validated);

        return response()->json(['message' => 'Order updated successfully', 'order' => $order->fresh()->load('client:id,name')]);
    }

    public function destroy(Order $order)
    {
        $order->delete();
        return response()->json(['message' => 'Order deleted successfully']);
    }
}
