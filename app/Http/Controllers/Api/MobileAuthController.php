<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\App;

class MobileAuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        $token = $user->createToken('mobile_api_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'token' => $token,
            'user' => $user,
        ]);
    }

    public function requestWhatsAppOtp(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string',
        ]);

        $contact = Contact::where('value', $request->identifier)
            ->where('type', 'whatsapp')
            ->first();

        if (!$contact || !$contact->user) {
            return response()->json(['message' => 'Contact not found or invalid.'], 404);
        }

        $cacheKey = 'otp_lock_' . $contact->id;

        if (Cache::has($cacheKey) && !App::environment('production')) {
            $code = Cache::get('otp_code_' . $contact->id);
            Log::info("[DEV/TEST] WhatsApp OTP for {$contact->value}: {$code}");
            return response()->json(['message' => 'Verification code sent (mocked).']);
        }

        $plainCode = (string) random_int(100000, 999999);
        if(!App::environment('production')) {
            Log::info("[DEV/TEST] WhatsApp OTP for {$contact->value}: {$plainCode}");
        }

        $contact->update([
            'verification_code' => Hash::make($plainCode),
            'code_expires_at'   => now()->addMinutes(10),
        ]);

        Cache::put($cacheKey, true, now()->addMinutes(5));
        Cache::put('otp_code_' . $contact->id, $plainCode, now()->addMinutes(10));

        try {
            $client = new Client(
                config('services.twilio.sid'),
                config('services.twilio.token')
            );

            $client->messages->create(
                "whatsapp:{$contact->value}",
                [
                    'from' => config('services.twilio.whatsapp_from'),
                    'body' => "Your Martin Logistics verification code is {$plainCode}. It expires in 10 minutes.",
                ]
            );
        } catch (\Throwable $e) {
            Log::error('Twilio WhatsApp send failed: ' . $e->getMessage());
            Cache::forget($cacheKey);
            return response()->json(['message' => 'Failed to send code via WhatsApp.'], 500);
        }

        return response()->json(['message' => 'Verification code sent via WhatsApp.']);
    }

    public function verifyWhatsAppOtp(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string',
            'code' => 'required|digits:6',
        ]);

        $contact = Contact::where('value', $request->identifier)
            ->where('type', 'whatsapp')
            ->first();

        if (!$contact || !$contact->user) {
            return response()->json(['message' => 'Contact not found.'], 404);
        }

        if (!$contact->verification_code || !$contact->code_expires_at) {
            return response()->json(['message' => 'No code has been requested.'], 422);
        }

        if (now()->greaterThan($contact->code_expires_at)) {
            return response()->json(['message' => 'Verification code expired.'], 422);
        }

        if (!Hash::check($request->code, $contact->verification_code)) {
            return response()->json(['message' => 'Invalid verification code.'], 422);
        }

        $contact->update([
            'verified_at' => now(),
            'verification_code' => null,
            'code_expires_at' => null,
        ]);

        $token = $contact->user->createToken('mobile_api_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'token' => $token,
            'user' => $contact->user,
        ]);
    }

    public function verifyFirebasePhone(Request $request)
    {
        $request->validate([
            'idToken' => 'required|string',
            'identifier' => 'required|string',
        ]);

        $contact = Contact::where('value', $request->identifier)
            ->where('type', 'phone')
            ->first();

        if (!$contact || !$contact->user) {
            return response()->json(['message' => 'Contact not found.'], 404);
        }

        if (!App::environment('production') && $request->idToken === 'TEST_BYPASS_TOKEN_123') {
            Log::info("[DEV/TEST] Bypassed Firebase verification for {$contact->value}");
            $contact->update(['verified_at' => now()]);
            return response()->json([
                'message' => 'Login successful (bypass).',
                'token' => $contact->user->createToken('mobile_api_token')->plainTextToken,
                'user' => $contact->user,
            ]);
        }

        try {
            $firebaseAuth = app(\Kreait\Firebase\Contract\Auth::class);
        } catch (\Throwable $e) {
            Log::warning('Firebase not configured: '.$e->getMessage());
            return response()->json(['message' => 'Firebase is not configured. Use WhatsApp OTP instead.'], 503);
        }

        try {
            $verifiedToken = $firebaseAuth->verifyIdToken($request->idToken);
            $phoneNumber   = $verifiedToken->claims()->get('phone_number');

            if (!$phoneNumber || $phoneNumber !== $contact->value) {
                return response()->json(['message' => 'Phone number mismatch or token invalid.'], 422);
            }

            $contact->update(['verified_at' => now()]);

            $token = $contact->user->createToken('mobile_api_token')->plainTextToken;

            return response()->json([
                'message' => 'Login successful.',
                'token' => $token,
                'user' => $contact->user,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Firebase verify failed: '.$e->getMessage());
            return response()->json(['message' => 'Invalid or expired Firebase token.'], 422);
        }
    }

    public function logout(Request $request)
    {
        if ($request->user() && $request->user()->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
        }

        return response()->json(['message' => 'Logged out successfully.']);
    }
}
