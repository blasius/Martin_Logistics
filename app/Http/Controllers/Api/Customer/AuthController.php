<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function signup(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'type' => 'required|in:company,individual',
            'tin' => 'nullable|string|max:255|required_if:type,company',
            'contact_person' => 'nullable|string|max:255',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $client = Client::create([
            'user_id' => $user->id,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'type' => $validated['type'],
            'tin' => $validated['tin'] ?? null,
            'contact_person' => $validated['contact_person'] ?? null,
        ]);

        // Send verification code (logs to laravel.log in dev mode)
        $user->sendEmailVerificationNotification();

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return response()->json([
            'message' => 'Account created. Please verify your email.',
            'user' => $user->load('client'),
            'requires_email_verification' => true,
        ], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::guard('web')->attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        $user = Auth::user();
        $request->session()->regenerate();

        if (!$user->client) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            throw ValidationException::withMessages([
                'email' => ['This account is not registered as a customer.'],
            ]);
        }

        if (!$user->hasVerifiedEmail()) {
            return response()->json([
                'requires_email_verification' => true,
                'email' => $user->email,
            ]);
        }

        return response()->json([
            'user' => $user->load('client'),
        ]);
    }

    public function verifyEmail(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.']);
        }

        if (
            $user->email_verification_code !== $request->code ||
            ($user->email_verification_code_expires_at && $user->email_verification_code_expires_at->isPast())
        ) {
            return response()->json(['message' => 'Invalid or expired verification code.'], 422);
        }

        $user->forceFill([
            'email_verified_at' => now(),
            'email_verification_code' => null,
            'email_verification_code_expires_at' => null,
        ])->save();

        return response()->json(['message' => 'Email verified successfully.']);
    }

    public function resendVerification(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.']);
        }

        $user->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification code sent.']);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return response()->json(['message' => 'Logged out.']);
    }

    public function me(Request $request)
    {
        $user = $request->user()->load('client');
        return response()->json($user);
    }
}
