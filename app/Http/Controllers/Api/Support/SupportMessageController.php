<?php

namespace App\Http\Controllers\Api\Support;

use App\Http\Controllers\Controller;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use Illuminate\Http\Request;

class SupportMessageController extends Controller
{
    public function store(Request $request, SupportTicket $ticket)
    {
        $data = $request->validate([
            'message' => 'required|string',
        ]);

        $message = SupportMessage::create([
            'support_ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'message' => $data['message'],
        ]);

        return response()->json($message->load('user'), 201);
    }
}
