<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;
use Kreait\Firebase\Auth as FirebaseAuth;
use Illuminate\Support\Facades\Auth;

class FirebaseVerificationController extends Controller
{
    protected FirebaseAuth $auth;
    public function __construct(FirebaseAuth $auth)
    {
        $this->auth = $auth;
    }

    public function verify(Contact $contact, Request $request)
    {
        abort_unless($contact->user_id === Auth::id(), 403);
        $request->validate(['idToken' => 'required|string']);

        try {
            $verified = $this->auth->verifyIdToken($request->idToken);
            // $verified->claims() contains phone_number etc.
            $phone = $verified->claims()->get('phone_number') ?? null;
            // Ensure the phone_number from token matches contact.value
            if ($phone !== $contact->value) {
                return response()->json(['message'=>'Phone mismatch'], 422);
            }

            $contact->update(['verified_at' => now()]);

            return response()->json(['message'=>'Phone verified via Firebase']);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Invalid Firebase token'], 422);
        }
    }
}
