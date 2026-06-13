<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with('client.user:id,name')
            ->select('id', 'client_id', 'reference', 'origin', 'destination', 'pickup_date', 'status', 'price', 'created_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($order) {
                $order->client_name = $order->client?->user?->name;
                return $order;
            });
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

        return response()->json(['message' => 'Order created successfully', 'order' => $order->load('client.user:id,name')]);
    }

    public function show(Order $order)
    {
        $order->load('client.user:id,name');
        return response()->json($order);
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

        return response()->json(['message' => 'Order updated successfully', 'order' => $order->fresh()->load('client.user:id,name')]);
    }

    public function destroy(Order $order)
    {
        $order->delete();
        return response()->json(['message' => 'Order deleted successfully']);
    }
}
