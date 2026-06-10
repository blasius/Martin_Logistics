<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportTicketEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MobileSupportTicketController extends Controller
{
    public function index(Request $request)
    {
        return SupportTicket::query()
            ->where('user_id', Auth::id())
            ->with([
                'user:id,name,email',
                'category:id,name',
                'subject' => fn($q) => $q->with(['trailers', 'assignments']),
            ])
            ->when($request->category_id, fn($q) => $q->where('support_category_id', $request->category_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->priority, fn($q) => $q->where('priority', $request->priority))
            ->when($request->search, function ($q) use ($request) {
                $s = $request->search;
                $q->where(function ($q) use ($s) {
                    $q->where('reference', 'like', "%{$s}%")
                      ->orWhere('title', 'like', "%{$s}%")
                      ->orWhere('description', 'like', "%{$s}%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'support_category_id' => 'required|exists:support_categories,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:low,normal,high,urgent',
            'subject_type' => 'nullable|string',
            'subject_id' => 'nullable|integer',
        ]);

        $validated['user_id'] = Auth::id();

        $ticket = SupportTicket::create($validated);

        $ticket->events()->create([
            'user_id' => Auth::id(),
            'type' => 'created',
            'payload' => ['priority' => $validated['priority'], 'source' => 'mobile'],
        ]);

        return response()->json($ticket->load([
            'user:id,name,email',
            'category:id,name',
            'subject',
        ]), 201);
    }

    public function show($id)
    {
        $ticket = SupportTicket::with([
            'user:id,name,email',
            'category:id,name',
            'assignee:id,name',
            'messages' => fn($q) => $q->with('author:id,name')->orderBy('created_at', 'asc'),
            'events' => fn($q) => $q->with('actor:id,name')->orderBy('created_at', 'desc'),
            'subject',
        ])->findOrFail($id);

        if ($ticket->user_id !== Auth::id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json($ticket);
    }

    public function addMessage(Request $request, SupportTicket $ticket)
    {
        if ($ticket->user_id !== Auth::id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'message' => 'required|string',
        ]);

        $message = $ticket->messages()->create([
            'message' => $data['message'],
            'user_id' => Auth::id(),
            'is_internal' => false,
        ]);

        $ticket->events()->create([
            'user_id' => Auth::id(),
            'type' => 'message_added',
            'payload' => ['source' => 'mobile'],
        ]);

        return response()->json($message->load('author:id,name'), 201);
    }
}
