<?php

namespace App\Services;

use App\Models\SupportCategory;
use App\Models\SupportTicket;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\SupportTicketAssigned;
use Illuminate\Database\Eloquent\Model;

/**
 * Shared "open auto-ticket" workflow used by anomaly detection across the
 * platform: fuel reconciliation (point 5), route deviations (point 12),
 * fines (point 11) and delays. All auto-created tickets route through this
 * service so the support stack stays the single inbox.
 */
class SupportAutoTicketService
{
    public function __construct(protected DispatcherAssignmentService $dispatcherAssignment) {}
    /**
     * Open an auto-generated support ticket for an anomaly.
     *
     * @param  array{title:string, description:string, priority?:string, source?:string}|array  $attrs
     * @param  Model|null  $subject  Morpheable anomaly subject (Vehicle, Trip, Driver, ...).
     * @param  string|null  $categoryName  SupportCategory name, resolved/created on demand.
     * @param  int|null  $assignedTo  Optional dispatcher/owner the ticket is routed to.
     * @param  int|null  $createdBy  User id recorded as the creating actor (system/admin).
     * @param  bool  $dedupe  Skip creation when an identical open ticket already exists.
     */
    public function open(
        array $attrs,
        ?Model $subject = null,
        ?string $categoryName = 'Fuel',
        ?int $assignedTo = null,
        ?int $createdBy = null,
        bool $dedupe = true,
    ): SupportTicket {
        $source = $attrs['source'] ?? 'auto';
        $priority = $attrs['priority'] ?? SupportTicket::PRIORITY_NORMAL;
        $title = $attrs['title'];
        $description = $attrs['description'];

        if ($dedupe && $this->hasOpenTicket($source, $subject, $title)) {
            return SupportTicket::where('source', $source)
                ->whereIn('status', [SupportTicket::STATUS_OPEN, SupportTicket::STATUS_IN_PROGRESS, SupportTicket::STATUS_WAITING])
                ->where('title', $title)
                ->when($subject, fn ($q) => $q->whereMorphedTo('subject', $subject))
                ->latest()
                ->firstOrFail();
        }

        $assignedTo ??= $this->resolveOwnerForSubject($subject);
        $category = $this->resolveCategory($categoryName);

        $ticket = SupportTicket::create([
            'support_category_id' => $category->id,
            'user_id' => $createdBy ?? $this->resolveSystemUserId(),
            'assigned_to' => $assignedTo,
            'subject_type' => $subject ? $subject->getMorphClass() : null,
            'subject_id' => $subject ? $subject->getKey() : null,
            'title' => $title,
            'description' => $description,
            'priority' => $priority,
            'status' => SupportTicket::STATUS_OPEN,
            'source' => $source,
        ]);

        $ticket->events()->create([
            'user_id' => $ticket->user_id,
            'type' => 'created',
            'payload' => [
                'via' => $source,
                'subject_type' => $ticket->subject_type,
                'subject_id' => $ticket->subject_id,
                'assigned_to' => $assignedTo,
            ],
        ]);

        if ($assignedTo) {
            $assignee = User::find($assignedTo);
            $assignee?->notify(new SupportTicketAssigned($ticket));
        }

        return $ticket;
    }

    /**
     * Route tickets touching a dispatcher-owned vehicle to that dispatcher
     * (per-vehicle single dispatcher ownership from the dispatch load work).
     */
    private function resolveOwnerForSubject(?Model $subject): ?int
    {
        $vehicleId = match (true) {
            $subject instanceof Vehicle => $subject->getKey(),
            $subject instanceof Trip => $subject->vehicle_id,
            default => null,
        };

        if (!$vehicleId) return null;

        return $this->dispatcherAssignment->ownerOf((int) $vehicleId)?->id;
    }

    public function hasOpenTicket(string $source, ?Model $subject, ?string $title = null, int $withinDays = 7): bool
    {
        return SupportTicket::where('source', $source)
            ->whereIn('status', [SupportTicket::STATUS_OPEN, SupportTicket::STATUS_IN_PROGRESS, SupportTicket::STATUS_WAITING])
            ->when($subject, fn ($q) => $q->whereMorphedTo('subject', $subject))
            ->when($title, fn ($q) => $q->where('title', $title))
            ->where('created_at', '>=', now()->subDays($withinDays))
            ->exists();
    }

    public function resolveCategory(string $name): SupportCategory
    {
        return SupportCategory::firstOrCreate(
            ['name' => $name],
            ['description' => "Auto-ticketing category: {$name}", 'is_active' => true]
        );
    }

    private function resolveSystemUserId(): ?int
    {
        $user = User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['super_admin', 'admin', 'dispatcher']))
            ->orderBy('id')
            ->first();

        return $user?->id;
    }
}