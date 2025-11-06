<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;
use Kreait\Firebase\Auth as FirebaseAuth;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FirebaseVerificationController extends Controller
{
    protected FirebaseAuth $auth;

    public function __construct(FirebaseAuth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Verify a phone contact using Firebase ID token.
     * The client must first complete Firebase phone auth and send us the idToken.
     */
    public function verify(Contact $contact, Request $request)
    {
        abort_unless($contact->user_id === Auth::id(), 403);
        $request->validate(['idToken' => 'required|string']);

        try {
            $verifiedToken = $this->auth->verifyIdToken($request->idToken);
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
