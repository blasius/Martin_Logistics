<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\SupportTicketEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupportTicketController extends Controller
{
    /**
     * Get tickets filtered by category and status
     */
    public function index(Request $request)
    {
        return SupportTicket::query()
            ->with(['user', 'category', 'assignedTo'])
            ->when($request->category_id, fn($q) => $q->where('support_category_id', $request->category_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderByRaw("FIELD(priority, 'urgent', 'high', 'normal', 'low')")
            ->orderBy('created_at', 'desc')
            ->paginate(20);
    }

    /**
     * Fetch full conversation and context for a single ticket
     */
    public function show($id)
    {
        $ticket = SupportTicket::with([
            'messages' => fn($q) => $q->orderBy('created_at', 'asc'),
            'events.user',
            'subject' // This pulls the Vehicle/Trip/Invoice automatically
        ])->findOrFail($id);

        // Record a 'view' event if the dispatcher hasn't seen it yet
        $this->logEvent($ticket->id, 'dispatcher_viewed', ['user' => auth()->user()->name]);

        return $ticket;
    }

    /**
     * Claim a ticket or change status
     */
    public function updateStatus(Request $request, SupportTicket $ticket)
    {
        $oldStatus = $ticket->status;
        $ticket->update($request->only(['status', 'assigned_to', 'priority']));

        // Log the change for the Audit Trail
        if ($oldStatus !== $ticket->status) {
            $this->logEvent($ticket->id, 'status_changed', [
                'from' => $oldStatus,
                'to' => $ticket->status
            ]);
        }

        return response()->json(['message' => 'Ticket updated']);
    }

    /**
     * Store a new message (Dispatcher/Manager reply)
     */
    public function reply(Request $request, SupportTicket $ticket)
    {
        $validated = $request->validate([
            'message' => 'required|string',
            'is_internal' => 'boolean'
        ]);

        $message = $ticket->messages()->create([
            'user_id' => auth()->id(),
            'message' => $validated['message'],
            'is_internal' => $validated['is_internal'] ?? false,
        ]);

        // If it's the first human reply, update the SLA timestamp
        if (!$ticket->first_response_at && !$message->is_internal) {
            $ticket->update(['first_response_at' => now()]);
        }

        return response()->json($message, 201);
    }

    private function logEvent($ticketId, $type, $payload = [])
    {
        SupportTicketEvent::create([
            'support_ticket_id' => $ticketId,
            'user_id' => auth()->id(),
            'type' => $type,
            'payload' => $payload
        ]);
    }
}
