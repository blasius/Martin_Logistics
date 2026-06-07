<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

class AuthController extends Controller
{
    public function portalLogin(Request $request)
    {
        $credentials = $request->validate([
            'identifier' => 'required|email',
            'password'   => 'required|string',
        ]);

        $attemptCredentials = [
            'email'    => $credentials['identifier'],
            'password' => $credentials['password'],
        ];

        if (! Auth::guard('web')->attempt($attemptCredentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'identifier' => [__('auth.failed')],
            ]);
        }

        $user = Auth::user();

        if (app()->environment('production') && ! $user->hasVerifiedEmail()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->json([
                'requires_email_verification' => true,
                'email' => $user->email,
                'message' => 'Please verify your email address before logging in.',
            ]);
        }

        if ($user->hasEnabledTwoFactorAuthentication()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $tempToken = Crypt::encrypt([
                'user_id' => $user->id,
                'expires_at' => now()->addMinutes(5)->timestamp,
            ]);

            return response()->json([
                'requires_2fa' => true,
                'temp_token' => $tempToken,
            ]);
        }

        // Force 2FA setup if not configured
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $tempToken = Crypt::encrypt([
            'user_id' => $user->id,
            'expires_at' => now()->addMinutes(10)->timestamp,
        ]);

        return response()->json([
            'requires_2fa_setup' => true,
            'temp_token' => $tempToken,
        ]);

        $request->session()->regenerate();
        $userData = $user->toArray();
        $userData['roles_list'] = $user->getRoleNames();

        return response()->json([
            'user'    => $userData,
            'message' => 'Authenticated via session',
        ]);
    }

    public function verifyTwoFactor(Request $request)
    {
        $request->validate([
            'temp_token' => 'required|string',
            'code'       => 'required|string',
        ]);

        try {
            $payload = Crypt::decrypt($request->temp_token);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Invalid or expired token.'], 422);
        }

        if (now()->timestamp > ($payload['expires_at'] ?? 0)) {
            return response()->json(['message' => 'Token expired. Please login again.'], 422);
        }

        $user = \App\Models\User::find($payload['user_id']);
        if (! $user || ! $user->hasEnabledTwoFactorAuthentication()) {
            return response()->json(['message' => 'Invalid token or 2FA not configured.'], 422);
        }

        $google2fa = app('pragmarx.google2fa');
        $valid = $google2fa->verifyKey(decrypt($user->two_factor_secret), $request->code);

        if (! $valid) {
            return response()->json(['message' => 'Invalid verification code.'], 422);
        }

        Auth::guard('web')->loginUsingId($user->id);
        $request->session()->regenerate();

        $userData = $user->toArray();
        $userData['roles_list'] = $user->getRoleNames();

        return response()->json([
            'user'    => $userData,
            'message' => 'Authenticated via 2FA',
        ]);
    }

    public function verifyRecoveryCode(Request $request)
    {
        $request->validate([
            'temp_token' => 'required|string',
            'code'       => 'required|string',
        ]);

        try {
            $payload = Crypt::decrypt($request->temp_token);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Invalid or expired token.'], 422);
        }

        if (now()->timestamp > ($payload['expires_at'] ?? 0)) {
            return response()->json(['message' => 'Token expired. Please login again.'], 422);
        }

        $user = \App\Models\User::find($payload['user_id']);
        if (! $user || ! $user->hasEnabledTwoFactorAuthentication()) {
            return response()->json(['message' => 'Invalid token or 2FA not configured.'], 422);
        }

        $recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);

        $foundIndex = false;
        foreach ($recoveryCodes as $i => $rc) {
            if (hash_equals($rc, $request->code)) {
                $foundIndex = $i;
                break;
            }
        }

        if ($foundIndex === false) {
            return response()->json(['message' => 'Invalid recovery code.'], 422);
        }

        unset($recoveryCodes[$foundIndex]);
        $user->forceFill([
            'two_factor_recovery_codes' => encrypt(json_encode(array_values($recoveryCodes))),
        ])->save();

        Auth::guard('web')->loginUsingId($user->id);
        $request->session()->regenerate();

        $userData = $user->toArray();
        $userData['roles_list'] = $user->getRoleNames();

        return response()->json([
            'user'    => $userData,
            'message' => 'Authenticated via recovery code',
        ]);
    }

    public function getTwoFactorQrCode(Request $request)
    {
        $user = $request->user();

        if (! $user->two_factor_secret) {
            $secret = app('pragmarx.google2fa')->generateSecretKey();
            $user->forceFill([
                'two_factor_secret' => encrypt($secret),
                'two_factor_confirmed_at' => null,
            ])->save();
        }

        return response()->json([
            'secret' => decrypt($user->two_factor_secret),
            'qr_code' => $user->twoFactorQrCodeSvg(),
            'qr_code_url' => $user->twoFactorQrCodeUrl(),
        ]);
    }

    public function enableTwoFactor(Request $request)
    {
        $user = $request->user();

        $google2fa = app('pragmarx.google2fa');
        $secret = $google2fa->generateSecretKey();

        $recoveryCodes = collect(range(1, 8))->map(fn () => \Illuminate\Support\Str::random(10))->all();

        $user->forceFill([
            'two_factor_secret' => encrypt($secret),
            'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
            'two_factor_confirmed_at' => null,
        ])->save();

        return response()->json([
            'secret' => $secret,
            'qr_code' => $user->twoFactorQrCodeSvg(),
            'qr_code_url' => $user->twoFactorQrCodeUrl(),
            'recovery_codes' => $recoveryCodes,
            'message' => 'Scan the QR code with your authenticator app, then confirm by entering a code.',
        ]);
    }

    public function confirmTwoFactor(Request $request)
    {
        $request->validate(['code' => 'required|string']);

        $user = $request->user();

        if (! $user->two_factor_secret) {
            return response()->json(['message' => '2FA not enabled yet. Call enable first.'], 422);
        }

        $google2fa = app('pragmarx.google2fa');
        $valid = $google2fa->verifyKey(decrypt($user->two_factor_secret), $request->code);

        if (! $valid) {
            return response()->json(['message' => 'Invalid code. Ensure your authenticator app is set up correctly.'], 422);
        }

        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        return response()->json(['message' => 'Two-factor authentication confirmed and active.']);
    }

    public function disableTwoFactor(Request $request)
    {
        $request->validate(['password' => 'required|string']);

        $user = $request->user();

        if (! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid password.'], 422);
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return response()->json(['message' => 'Two-factor authentication disabled.']);
    }

    public function getRecoveryCodes(Request $request)
    {
        $user = $request->user();

        if (! $user->two_factor_recovery_codes) {
            return response()->json(['recovery_codes' => []]);
        }

        $codes = json_decode(decrypt($user->two_factor_recovery_codes), true);

        return response()->json(['recovery_codes' => $codes]);
    }

    public function regenerateRecoveryCodes(Request $request)
    {
        $user = $request->user();

        $codes = collect(range(1, 8))->map(fn () => \Illuminate\Support\Str::random(10))->all();

        $user->forceFill([
            'two_factor_recovery_codes' => encrypt(json_encode($codes)),
        ])->save();

        return response()->json([
            'recovery_codes' => $codes,
            'message' => 'Recovery codes regenerated.',
        ]);
    }

    public function initSetup(Request $request)
    {
        $request->validate(['temp_token' => 'required|string']);

        try {
            $payload = Crypt::decrypt($request->temp_token);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Invalid or expired token.'], 422);
        }

        if (now()->timestamp > ($payload['expires_at'] ?? 0)) {
            return response()->json(['message' => 'Token expired. Please login again.'], 422);
        }

        $user = \App\Models\User::find($payload['user_id']);
        if (! $user) {
            return response()->json(['message' => 'Invalid token.'], 422);
        }

        if ($user->hasEnabledTwoFactorAuthentication()) {
            Auth::guard('web')->loginUsingId($user->id);
            $request->session()->regenerate();
            $userData = $user->toArray();
            $userData['roles_list'] = $user->getRoleNames();
            return response()->json(['user' => $userData, 'message' => '2FA already active.']);
        }

        if (! $user->two_factor_secret) {
            $google2fa = app('pragmarx.google2fa');
            $secret = $google2fa->generateSecretKey();
            $recoveryCodes = collect(range(1, 8))->map(fn () => \Illuminate\Support\Str::random(10))->all();

            $user->forceFill([
                'two_factor_secret' => encrypt($secret),
                'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
                'two_factor_confirmed_at' => null,
            ])->save();
        }

        return response()->json([
            'secret' => decrypt($user->two_factor_secret),
            'qr_code' => $user->twoFactorQrCodeSvg(),
            'qr_code_url' => $user->twoFactorQrCodeUrl(),
            'recovery_codes' => $user->two_factor_recovery_codes
                ? json_decode(decrypt($user->two_factor_recovery_codes), true)
                : [],
        ]);
    }

    public function confirmSetup(Request $request)
    {
        $request->validate([
            'temp_token' => 'required|string',
            'code' => 'required|string',
        ]);

        try {
            $payload = Crypt::decrypt($request->temp_token);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Invalid or expired token.'], 422);
        }

        if (now()->timestamp > ($payload['expires_at'] ?? 0)) {
            return response()->json(['message' => 'Token expired. Please login again.'], 422);
        }

        $user = \App\Models\User::find($payload['user_id']);
        if (! $user || ! $user->two_factor_secret) {
            return response()->json(['message' => 'Invalid token or 2FA not initialized.'], 422);
        }

        if ($user->hasEnabledTwoFactorAuthentication()) {
            Auth::guard('web')->loginUsingId($user->id);
            $request->session()->regenerate();
            $userData = $user->toArray();
            $userData['roles_list'] = $user->getRoleNames();
            return response()->json(['user' => $userData, 'message' => '2FA already active.']);
        }

        $google2fa = app('pragmarx.google2fa');
        $valid = $google2fa->verifyKey(decrypt($user->two_factor_secret), $request->code);

        if (! $valid) {
            return response()->json(['message' => 'Invalid verification code. Try again.'], 422);
        }

        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        Auth::guard('web')->loginUsingId($user->id);
        $request->session()->regenerate();

        $userData = $user->toArray();
        $userData['roles_list'] = $user->getRoleNames();

        return response()->json([
            'user' => $userData,
            'message' => '2FA setup complete.',
        ]);
    }

    public function resendVerificationEmail(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $user = \App\Models\User::where('email', $request->email)->first();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.']);
        }

        $user->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification email sent.']);
    }

    public function verifyEmail(Request $request, $id, $hash)
    {
        $user = \App\Models\User::findOrFail($id);

        if (! hash_equals(sha1($user->getEmailForVerification()), (string) $hash)) {
            return response()->json(['message' => 'Invalid verification link.'], 403);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.']);
        }

        $user->markEmailAsVerified();

        return response()->json(['message' => 'Email verified successfully. You can now log in.']);
    }

    public function login(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string',
            'password' => 'required|string',
        ]);

        $contact = Contact::where('value', $request->identifier)->first();

        if (! $contact || ! $contact->user || ! Hash::check($request->password, $contact->user->password)) {
            throw ValidationException::withMessages(['identifier' => [__('auth.failed')]]);
        }

        if (! $contact->isVerified()) {
            return response()->json(['message' => 'Verify your contact method first.'], 403);
        }

        return response()->json([
            'token' => $contact->user->createToken('api_token')->plainTextToken,
            'user' => $contact->user,
        ]);
    }

    public function logout(Request $request)
    {
        if ($request->user()->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out successfully']);
    }
}
