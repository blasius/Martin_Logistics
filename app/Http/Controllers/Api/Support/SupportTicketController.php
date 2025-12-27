<?php

namespace App\Http\Controllers\Api\Support;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use Illuminate\Http\Request;

class SupportTicketController extends Controller
{
    public function index(Request $request)
    {
        return SupportTicket::with(['category', 'assignee'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->priority, fn ($q) => $q->where('priority', $request->priority))
            ->latest()
            ->paginate(20);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => 'required|exists:support_categories,id',
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:low,medium,high,critical',
            'subject_type' => 'nullable|string',
            'subject_id' => 'nullable|integer',
        ]);

        $ticket = SupportTicket::create([
            ...$data,
            'created_by_id' => auth()->id(),
            'status' => 'open',
        ]);

        return response()->json($ticket->load('category'), 201);
    }

    public function show(SupportTicket $ticket)
    {
        return $ticket->load([
            'category',
            'assignee',
            'messages.user',
            'events'
        ]);
    }

    public function update(Request $request, SupportTicket $ticket)
    {
        $data = $request->validate([
            'status' => 'sometimes|in:open,in_progress,waiting,resolved,closed',
            'assigned_to_id' => 'nullable|exists:users,id',
        ]);

        $ticket->update($data);

        return response()->json($ticket->fresh());
    }
}
