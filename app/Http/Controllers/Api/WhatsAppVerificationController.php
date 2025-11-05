<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Twilio\Rest\Client;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class WhatsAppVerificationController extends Controller
{
    public function send(Contact $contact, Request $request)
    {
        abort_unless($contact->user_id === Auth::id(), 403);
        if ($contact->type !== 'whatsapp') return response()->json(['message'=>'Contact type mismatch'], 422);

        // rate limit: check last send
        if ($contact->code_expires_at && now()->lessThan($contact->code_expires_at->subMinutes(9))) {
            // option: prevent too frequent requests; adjust policy
        }

        $plain = random_int(100000, 999999); // 6-digit

        // Hash the code before storing
        $contact->update([
            'verification_code' => Hash::make((string)$plain),
            'code_expires_at' => now()->addMinutes(10),
        ]);

        // send via Twilio Whatsapp
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $from = config('services.twilio.whatsapp_from'); // e.g. whatsapp:+1415xxxxxxx
        $client = new Client($sid, $token);

        try {
            $client->messages->create(
                "whatsapp:{$contact->value}", // ensure contact.value is E.164 without "whatsapp:"
                [
                    'from' => $from,
                    'body' => "Your verification code is: {$plain}. It expires in 10 minutes."
                ]
            );
        } catch (\Throwable $e) {
            Log::error("Twilio send failed: " . $e->getMessage());
            return response()->json(['message' => 'Failed to sendWhatsApp'], 500);
        }

        Log::info("Sent WhatsApp code to {$contact->value} for user " . Auth::id());

        return response()->json(['message' => 'Verification code sent via WhatsApp']);
    }

    public function verify(Contact $contact, Request $request)
    {
        abort_unless($contact->user_id === Auth::id(), 403);
        $request->validate(['code' => 'required|digits:6']);

        if (! $contact->verification_code || ! $contact->code_expires_at) {
            return response()->json(['message' => 'No code requested'], 422);
        }
        if (now()->greaterThan($contact->code_expires_at)) {
            return response()->json(['message' => 'Code expired'], 422);
        }

        if (! Hash::check($request->code, $contact->verification_code)) {
            return response()->json(['message' => 'Invalid code'], 422);
        }

        $contact->update([
            'verified_at' => now(),
            'verification_code' => null,
            'code_expires_at' => null,
        ]);

        return response()->json(['message' => 'WhatsApp verified']);
    }
}
