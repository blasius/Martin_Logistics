<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactController extends Controller
{
    public function index()
    {
        return response()->json(Auth::user()->contacts);
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:email,phone,whatsapp',
            'value' => 'required|string|unique:contacts,value',
        ]);

        $contact = Contact::create([
            'user_id' => Auth::id(),
            'type' => $request->type,
            'value' => $request->value,
        ]);

        return response()->json($contact, 201);
    }
}
