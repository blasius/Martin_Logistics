<?php

namespace App\Http\Controllers\Api\Portal;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use Illuminate\Http\Request;

class SupportTicketController extends Controller
{
    public function index(Request $request)
    {
        return SupportTicket::with(['category', 'creator', 'assignee'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->priority, fn ($q) => $q->where('priority', $request->priority))
            ->latest()
            ->paginate(20);
    }

    public function update(Request $request, SupportTicket $ticket)
    {
        $data = $request->validate([
            'status' => 'nullable|in:OPEN,IN_PROGRESS,RESOLVED,CLOSED',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $ticket->update($data);

        return response()->json($ticket->load(['category', 'assignee']));
    }
}
