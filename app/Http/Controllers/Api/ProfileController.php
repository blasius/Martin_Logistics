<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Notifications\ProfilePasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user()->only(['id', 'name', 'email']);
        return response()->json($user);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $user->update($validated);

        return response()->json(['message' => 'Profile updated successfully', 'user' => $user->fresh()->only(['id', 'name', 'email'])]);
    }

    public function sendResetLink(Request $request)
    {
        $user = $request->user();
        $token = Password::broker()->createToken($user);
        $user->notify(new ProfilePasswordReset($token));

        return response()->json(['message' => 'Password reset link sent to your email.']);
    }

    public function reset(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $status = Password::broker()->reset(
            $validated,
            function ($user, $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return response()->json(['message' => 'Password reset successfully.']);
    }
}
