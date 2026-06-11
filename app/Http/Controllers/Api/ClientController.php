<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index()
    {
        $clients = Client::select('id', 'name', 'contact_person', 'phone', 'email', 'address', 'type', 'tin', 'created_at')
            ->orderBy('name')
            ->get();
        return response()->json($clients);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:255',
            'type' => 'required|in:company,individual',
            'tin' => 'nullable|string|max:255|required_if:type,company',
        ]);

        $client = Client::create($validated);

        return response()->json(['message' => 'Client created successfully', 'client' => $client]);
    }

    public function show(Client $client)
    {
        return response()->json($client);
    }

    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:255',
            'type' => 'required|in:company,individual',
            'tin' => 'nullable|string|max:255|required_if:type,company',
        ]);

        $client->update($validated);

        return response()->json(['message' => 'Client updated successfully', 'client' => $client]);
    }

    public function search(Request $request)
    {
        $q = $request->query('q');
        if (strlen($q) < 1) {
            return response()->json([]);
        }

        $clients = Client::where('name', 'like', "%{$q}%")
            ->orWhere('email', 'like', "%{$q}%")
            ->orWhere('phone', 'like', "%{$q}%")
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'email', 'phone']);

        return response()->json($clients);
    }

    public function destroy(Client $client)
    {
        $client->delete();
        return response()->json(['message' => 'Client deleted successfully']);
    }
}
