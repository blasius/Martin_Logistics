<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $orders = Order::where('client_id', $user->client->id)
            ->with('client.user:id,name')
            ->select('id', 'client_id', 'reference', 'origin', 'destination', 'pickup_date', 'status', 'price', 'created_at', 'updated_at')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($orders);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'origin' => 'required|string|max:255',
            'destination' => 'required|string|max:255',
            'pickup_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $user = $request->user();
        $client = $user->client;

        if (!$client) {
            return response()->json(['message' => 'No client profile found.'], 403);
        }

        $order = Order::create([
            'client_id' => $client->id,
            'origin' => $validated['origin'],
            'destination' => $validated['destination'],
            'pickup_date' => $validated['pickup_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => 'draft',
        ]);

        return response()->json([
            'message' => 'Order placed successfully.',
            'order' => $order->load('client.user:id,name'),
        ], 201);
    }

    public function show(Order $order, Request $request)
    {
        $user = $request->user();

        if ($order->client_id !== $user->client?->id) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        return response()->json($order->load('client.user:id,name'));
    }
}
