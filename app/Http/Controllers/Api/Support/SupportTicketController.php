<?php

namespace App\Http\Controllers\Api\Support;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use Illuminate\Http\Request;

class SupportTicketController extends Controller
{
    public function index(Request $request)
    {
        $query = SupportTicket::with(['category','assignedTo'])
            ->latest();

        if (!auth()->user()->isStaff()) {
            $query->where('user_id', auth()->id());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        return $query->paginate(20);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'support_category_id' => 'required|exists:support_categories,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'nullable|in:low,normal,high,urgent',
        ]);

        $ticket = SupportTicket::create([
            ...$data,
            'user_id' => auth()->id(),
            'reference' => SupportTicket::generateReference(),
        ]);

        $ticket->events()->create([
            'user_id' => auth()->id(),
            'type' => 'created',
        ]);

        return response()->json($ticket, 201);
    }

    public function show(SupportTicket $ticket)
    {
        $this->authorize('view', $ticket);

        return $ticket->load([
            'messages.user',
            'events.user',
            'category',
            'assignedTo',
        ]);
    }

    public function updateStatus(Request $request, SupportTicket $ticket)
    {
        $this->authorize('update', $ticket);

        $request->validate([
            'status' => 'required|in:open,in_progress,waiting,resolved,closed',
        ]);

        $old = $ticket->status;
        $ticket->update(['status' => $request->status]);

        $ticket->events()->create([
            'user_id' => auth()->id(),
            'type' => 'status_changed',
            'payload' => ['from' => $old, 'to' => $request->status],
        ]);

        return $ticket;
    }

    public function assign(Request $request, SupportTicket $ticket)
    {
        $request->validate([
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $ticket->update(['assigned_to' => $request->assigned_to]);

        $ticket->events()->create([
            'user_id' => auth()->id(),
            'type' => 'assigned',
            'payload' => ['assigned_to' => $request->assigned_to],
        ]);

        return $ticket;
    }
}
