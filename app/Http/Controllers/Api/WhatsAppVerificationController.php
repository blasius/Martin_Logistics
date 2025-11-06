<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;

class WhatsAppVerificationController extends Controller
{
    /**
     * Send a 6-digit verification code to the user's WhatsApp number.
     */
    public function send(Contact $contact, Request $request)
    {
        // Only the owner can request verification
        abort_unless($contact->user_id === Auth::id(), 403);

        if ($contact->type !== 'whatsapp') {
            return response()->json(['message' => 'Contact type must be whatsapp'], 422);
        }

        $plainCode = random_int(100000, 999999);

        $contact->update([
            'verification_code' => Hash::make((string) $plainCode),
            'code_expires_at'   => now()->addMinutes(10),
        ]);

        try {
            $client = new Client(
                config('services.twilio.sid'),
                config('services.twilio.token')
            );

            $client->messages->create(
                "whatsapp:{$contact->value}", // E.164 format number
                [
                    'from' => config('services.twilio.whatsapp_from'),
                    'body' => "Your Martin Logistics verification code is {$plainCode}. It expires in 10 minutes.",
                ]
            );
        } catch (\Throwable $e) {
            Log::error('Twilio WhatsApp send failed: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to send code'], 500);
        }

        return response()->json(['message' => 'Verification code sent via WhatsApp']);
    }

    /**
     * Verify the WhatsApp code submitted by the user.
     */
    public function verify(Contact $contact, Request $request)
    {
        abort_unless($contact->user_id === Auth::id(), 403);
        $request->validate(['code' => 'required|digits:6']);

        if (! $contact->verification_code || ! $contact->code_expires_at) {
            return response()->json(['message' => 'No code has been requested'], 422);
        }

        if (now()->greaterThan($contact->code_expires_at)) {
            return response()->json(['message' => 'Verification code expired'], 422);
        }

        if (! Hash::check($request->code, $contact->verification_code)) {
            return response()->json(['message' => 'Invalid verification code'], 422);
        }

        $contact->update([
            'verified_at'        => now(),
            'verification_code'  => null,
            'code_expires_at'    => null,
        ]);

        return response()->json(['message' => 'WhatsApp contact verified successfully']);
    }
}
