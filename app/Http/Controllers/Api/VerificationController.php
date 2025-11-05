<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class VerificationController extends Controller
{
    public function sendCode(Contact $contact)
    {
        abort_unless($contact->user_id === Auth::id(), 403);

        $code = rand(100000, 999999);
        $contact->update([
            'verification_code' => $code,
            'code_expires_at' => now()->addMinutes(10),
        ]);

        // You’d integrate Twilio, Mail, or WhatsApp API here
        Log::info("Verification code {$code} sent to {$contact->value}");

        return response()->json(['message' => 'Verification code sent.']);
    }

    public function verify(Contact $contact, Request $request)
    {
        abort_unless($contact->user_id === Auth::id(), 403);

        $request->validate(['code' => 'required|digits:6']);

        if (
            $contact->verification_code === $request->code &&
            now()->lessThanOrEqualTo($contact->code_expires_at)
        ) {
            $contact->update([
                'verified_at' => now(),
                'verification_code' => null,
                'code_expires_at' => null,
            ]);

            return response()->json(['message' => 'Contact verified successfully.']);
        }

        return response()->json(['message' => 'Invalid or expired code.'], 422);
    }
}
