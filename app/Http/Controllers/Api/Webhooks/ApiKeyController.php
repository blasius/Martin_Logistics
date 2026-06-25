<?php

namespace App\Http\Controllers\Api\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiKeyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $keys = ApiKey::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get(['id', 'name', 'last_used_at', 'expires_at', 'created_at', 'is_active']);

        return response()->json($keys);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $plaintext = Str::random(32);
        $keyPrefix = 'ml_' . Str::random(8) . '_';

        $apiKey = ApiKey::create([
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
            'key' => hash('sha256', $keyPrefix . $plaintext),
            'expires_at' => $validated['expires_at'] ?? null,
        ]);

        return response()->json([
            'id' => $apiKey->id,
            'name' => $apiKey->name,
            'plain_text_key' => $keyPrefix . $plaintext,
            'expires_at' => $apiKey->expires_at,
        ], 201);
    }

    public function destroy(ApiKey $apiKey): JsonResponse
    {
        $apiKey->update(['is_active' => false]);
        return response()->json(['message' => 'API key revoked.']);
    }
}
