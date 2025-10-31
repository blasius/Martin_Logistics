<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ContactVerificationController extends Controller
{
    // list contacts for current user
    public function index()
    {
        $user = Auth::user();
        return response()->json($user->contacts()->get());
    }

    // add contact & send code
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:email,phone,whatsapp,telegram,other',
            'value' => 'required|string|max:255',
        ]);

        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // prevent duplicates per user/type/value
        $exists = $user->contacts()->where('type', $request->type)->where('value', $request->value)->first();
        if ($exists) {
            return response()->json(['message' => 'Contact already exists'], 409);
        }

        $code = random_int(100000, 999999);
        $contact = $user->contacts()->create([
            'type' => $request->type,
            'value' => $request->value,
            'verification_code' => (string)$code,
            'code_expires_at' => now()->addMinutes(10),
        ]);

        // TODO: replace Log::info with real sender integration (mail, SMS, WhatsApp)
        Log::info("Verification code for contact {$contact->value}: {$contact->verification_code}");

        return response()->json([
            'message' => 'Verification code sent (check logs in dev).',
            'contact' => $contact,
        ], 201);
    }

    // send code again for an existing contact (resend)
    public function resend(Contact $contact)
    {
        $this->authorize('update', $contact);

        $contact->update([
            'verification_code' => (string) random_int(100000, 999999),
            'code_expires_at' => now()->addMinutes(10),
        ]);

        Log::info("Resent verification code for {$contact->value}: {$contact->verification_code}");

        return response()->json(['message' => 'Verification code resent.']);
    }

    // verify code
    public function verify(Request $request, Contact $contact)
    {
        $this->authorize('update', $contact);

        $request->validate(['code' => 'required|string']);

        if (! $contact->verification_code || $contact->verification_code !== $request->code) {
            return response()->json(['message' => 'Invalid code'], 422);
        }

        if ($contact->code_expires_at && now()->greaterThan($contact->code_expires_at)) {
            return response()->json(['message' => 'Code expired'], 422);
        }

        $contact->update([
            'verified_at' => now(),
            'verification_code' => null,
            'code_expires_at' => null,
        ]);

        return response()->json(['message' => 'Contact verified', 'contact' => $contact]);
    }
}
