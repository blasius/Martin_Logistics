<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string', // email, phone, or whatsapp
            'password' => 'required|string',
        ]);

        // Look for the contact
        $contact = Contact::where('value', $request->identifier)->first();

        if (! $contact) {
            throw ValidationException::withMessages([
                'identifier' => ['No account found for this contact method.'],
            ]);
        }

        $user = $contact->user;

        if (! $user || ! \Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'identifier' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Optional: only allow login if the contact is verified
        if (! $contact->isVerified()) {
            return response()->json([
                'message' => 'Please verify your contact method before logging in.'
            ], 403);
        }

        $token = $user->createToken('api_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }
}
