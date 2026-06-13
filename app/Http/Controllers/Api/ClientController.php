<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ClientController extends Controller
{
    public function index()
    {
        $clients = Client::with('user:id,name,email')
            ->select('id', 'user_id', 'contact_person', 'phone', 'address', 'type', 'tin', 'created_at')
            ->orderBy('id')
            ->get()
            ->map(function ($client) {
                $client->name = $client->user?->name;
                $client->email = $client->user?->email;
                return $client;
            });
        return response()->json($clients);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255|unique:users,email',
            'address' => 'nullable|string|max:255',
            'type' => 'required|in:company,individual',
            'tin' => 'nullable|string|max:255|required_if:type,company',
        ]);

        $user = null;
        if (!empty($validated['email'])) {
            $password = $request->input('password', \Illuminate\Support\Str::password(12));
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($password),
            ]);
        }

        $client = Client::create([
            'user_id' => $user?->id,
            'contact_person' => $validated['contact_person'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'type' => $validated['type'],
            'tin' => $validated['tin'] ?? null,
        ]);

        return response()->json(['message' => 'Client created successfully', 'client' => $client->load('user:id,name,email')]);
    }

    public function show(Client $client)
    {
        $client->load('user:id,name,email');
        $client->name = $client->user?->name;
        $client->email = $client->user?->email;
        return response()->json($client);
    }

    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255|unique:users,email,' . ($client->user_id ?? 'NULL'),
            'address' => 'nullable|string|max:255',
            'type' => 'required|in:company,individual',
            'tin' => 'nullable|string|max:255|required_if:type,company',
        ]);

        if ($client->user) {
            $client->user->update([
                'name' => $validated['name'],
                'email' => $validated['email'] ?? $client->user->email,
            ]);
        }

        $client->update([
            'contact_person' => $validated['contact_person'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'type' => $validated['type'],
            'tin' => $validated['tin'] ?? null,
        ]);

        return response()->json(['message' => 'Client updated successfully', 'client' => $client->fresh()->load('user:id,name,email')]);
    }

    public function search(Request $request)
    {
        $q = $request->query('q');
        if (strlen($q) < 1) {
            return response()->json([]);
        }

        $clients = Client::with('user:id,name,email')
            ->whereHas('user', function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            })
            ->orWhere('phone', 'like', "%{$q}%")
            ->orderBy('id')
            ->limit(20)
            ->get()
            ->map(function ($client) {
                return [
                    'id' => $client->id,
                    'name' => $client->user?->name ?? 'N/A',
                    'email' => $client->user?->email,
                    'phone' => $client->phone,
                ];
            });

        return response()->json($clients);
    }

    public function destroy(Client $client)
    {
        $client->delete();
        return response()->json(['message' => 'Client deleted successfully']);
    }
}
