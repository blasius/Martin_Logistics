<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FirebaseVerificationController extends Controller
{
    public function verify(Contact $contact, Request $request)
    {
        abort_unless($contact->user_id === Auth::id(), 403);
        $request->validate(['idToken' => 'required|string']);

        try {
            $firebaseAuth = app(\Kreait\Firebase\Contract\Auth::class);
        } catch (\Throwable $e) {
            Log::warning('Firebase not configured: '.$e->getMessage());
            return response()->json(['message' => 'Firebase is not configured. Contact support.'], 503);
        }

        try {
            $verifiedToken = $firebaseAuth->verifyIdToken($request->idToken);
            $phoneNumber   = $verifiedToken->claims()->get('phone_number');

            if (! $phoneNumber) {
                return response()->json(['message' => 'Token does not contain a phone number'], 422);
            }

            if ($phoneNumber !== $contact->value) {
                return response()->json(['message' => 'Phone number mismatch'], 422);
            }

            $contact->update([
                'verified_at' => now(),
                'verification_code' => null,
                'code_expires_at'   => null,
            ]);

            return response()->json(['message' => 'Phone verified successfully via Firebase']);
        } catch (\Throwable $e) {
            Log::warning('Firebase verify failed: '.$e->getMessage());
            return response()->json(['message' => 'Invalid or expired Firebase token'], 422);
        }
    }
}
