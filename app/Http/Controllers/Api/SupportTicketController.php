<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportTicketEvent;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupportTicketController extends Controller
{
    public function index(Request $request)
    {
        $tickets = SupportTicket::query()
            ->with([
                'user:id,name,email',
                'user.driver:id,user_id,phone',
                'category:id,name',
                'assignee:id,name',
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
                      ->orWhere('description', 'like', "%{$s}%")
                      ->orWhereHas('user', fn($q) => $q->where('name', 'like', "%{$s}%"));
                });
            })
            ->orderByRaw("FIELD(priority, 'urgent', 'high', 'normal', 'low')")
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        $this->attachCurrentVehicles($tickets);

        return $tickets;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'support_category_id' => 'required|exists:support_categories,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:low,normal,high,urgent',
            'subject_type' => 'nullable|string',
            'subject_id' => 'nullable|integer',
        ]);

        $ticket = DB::transaction(function () use ($validated) {
            $ticket = SupportTicket::create($validated);

            $ticket->events()->create([
                'user_id' => auth()->id(),
                'type' => 'created',
                'payload' => ['priority' => $validated['priority']],
            ]);

            return $ticket;
        });

        return response()->json($ticket->load([
            'user:id,name,email',
            'user.driver:id,user_id,phone',
            'category:id,name',
            'assignee:id,name',
            'subject',
        ]), 201);
    }

    public function show($id)
    {
        $ticket = SupportTicket::with([
            'user:id,name,email',
            'user.driver:id,user_id,phone',
            'category:id,name',
            'assignee:id,name',
            'messages' => fn($q) => $q->with('author:id,name')->orderBy('created_at', 'asc'),
            'events' => fn($q) => $q->with('actor:id,name')->orderBy('created_at', 'desc'),
            'subject',
        ])->findOrFail($id);

        $this->attachCurrentVehicleToTicket($ticket);

        $this->logEvent($ticket->id, 'dispatcher_viewed');

        return response()->json($ticket);
    }

    public function updateStatus(Request $request, SupportTicket $ticket)
    {
        $validated = $request->validate([
            'status' => 'required|in:open,in_progress,waiting,resolved,closed',
        ]);

        $oldStatus = $ticket->status;
        $ticket->update($validated);

        if ($validated['status'] === 'resolved') {
            $ticket->update(['resolved_at' => now()]);
        }

        if ($validated['status'] === 'closed') {
            $ticket->update(['closed_at' => now()]);
        }

        $this->logEvent($ticket->id, 'status_changed', [
            'from' => $oldStatus,
            'to' => $validated['status'],
        ]);

        return response()->json(['message' => 'Status updated', 'ticket' => $ticket]);
    }

    public function assign(Request $request, SupportTicket $ticket)
    {
        $validated = $request->validate([
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $oldAssignee = $ticket->assigned_to;
        $ticket->update($validated);

        $this->logEvent($ticket->id, 'assigned', [
            'from' => $oldAssignee,
            'to' => $validated['assigned_to'],
        ]);

        return response()->json([
            'message' => $validated['assigned_to'] ? 'Ticket assigned' : 'Ticket unassigned',
            'ticket' => $ticket->load('assignee:id,name'),
        ]);
    }

    public function categoryStats()
    {
        $tickets = SupportTicket::selectRaw('support_category_id, status, count(*) as count')
            ->groupBy('support_category_id', 'status')
            ->get()
            ->groupBy('support_category_id');

        $categories = \App\Models\SupportCategory::where('is_active', true)
            ->withCount('tickets')
            ->get()
            ->map(function ($cat) use ($tickets) {
                $stats = $tickets->get($cat->id, collect());
                return [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'total' => $cat->tickets_count,
                    'open' => $stats->where('status', 'open')->sum('count'),
                    'in_progress' => $stats->where('status', 'in_progress')->sum('count'),
                    'resolved' => $stats->where('status', 'resolved')->sum('count'),
                ];
            });

        return response()->json($categories);
    }

    public function searchUsers(Request $request)
    {
        $q = $request->query('q');

        return User::where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                      ->orWhere('email', 'like', "%{$q}%");
            })
            ->select('id', 'name', 'email')
            ->limit(15)
            ->get();
    }

    public function searchVehicles(Request $request)
    {
        $q = $request->query('q');

        return Vehicle::where('plate_number', 'like', "%{$q}%")
            ->orWhere('make', 'like', "%{$q}%")
            ->select('id', 'plate_number', 'make', 'model')
            ->limit(15)
            ->get();
    }

    private function attachCurrentVehicles($tickets)
    {
        $driverUserIds = collect($tickets->items())
            ->filter(fn($t) => $t->user && $t->user->driver)
            ->pluck('user_id')
            ->unique();

        if ($driverUserIds->isEmpty()) return;

        $activeVehicles = DB::table('driver_vehicle_assignments')
            ->join('vehicles', 'vehicles.id', '=', 'driver_vehicle_assignments.vehicle_id')
            ->whereIn('driver_vehicle_assignments.driver_id', $driverUserIds)
            ->whereNull('driver_vehicle_assignments.end_date')
            ->select(
                'driver_vehicle_assignments.driver_id',
                'vehicles.id',
                'vehicles.plate_number',
                'vehicles.make',
                'vehicles.model'
            )
            ->get()
            ->keyBy('driver_id');

        foreach ($tickets as $ticket) {
            $ticket->current_vehicle = $activeVehicles->get($ticket->user_id);
        }
    }

    private function attachCurrentVehicleToTicket($ticket)
    {
        if (!$ticket->user || !$ticket->user->driver) {
            $ticket->current_vehicle = null;
            return;
        }

        $vehicle = DB::table('driver_vehicle_assignments')
            ->join('vehicles', 'vehicles.id', '=', 'driver_vehicle_assignments.vehicle_id')
            ->where('driver_vehicle_assignments.driver_id', $ticket->user_id)
            ->whereNull('driver_vehicle_assignments.end_date')
            ->select('vehicles.id', 'vehicles.plate_number', 'vehicles.make', 'vehicles.model')
            ->first();

        $ticket->current_vehicle = $vehicle;
    }

    private function logEvent($ticketId, $type, $payload = [])
    {
        SupportTicketEvent::create([
            'support_ticket_id' => $ticketId,
            'user_id' => auth()->id(),
            'type' => $type,
            'payload' => $payload,
        ]);
    }
}
