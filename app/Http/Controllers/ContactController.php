<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactController extends Controller
{
    public function index()
    {
        return Auth::user()->contacts()->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:email,phone,whatsapp,telegram,other',
            'value' => 'required|string|max:255',
        ]);

        $contact = Auth::user()->contacts()->create($validated);

        return response()->json([
            'message' => 'Contact added successfully.',
            'contact' => $contact
        ]);
    }
}
