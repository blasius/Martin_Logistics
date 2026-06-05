<?php

namespace App\Http\Controllers\Api\Support;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use Illuminate\Http\Request;

class SupportTicketMessageController extends Controller
{
    public function store(Request $request, SupportTicket $ticket)
    {
        $data = $request->validate([
            'message' => 'required|string',
            'is_internal' => 'boolean',
        ]);

        $message = $ticket->messages()->create([
            ...$data,
            'user_id' => auth()->id(),
        ]);

        $user = auth()->user();

        if (
            $user->hasAnyRole(['super_admin', 'admin', 'operator'])
            && !$request->boolean('is_internal')
            && is_null($ticket->sla_first_response_at)
        ) {
            $ticket->update([
                'sla_first_response_at' => now(),
            ]);
        }

        $ticket->events()->create([
            'user_id' => auth()->id(),
            'type' => 'message_added',
        ]);

        return response()->json($message->load('author:id,name'), 201);
    }
}
