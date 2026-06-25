<?php

namespace App\Services;

use App\Models\EscalationRule;
use App\Models\SupportTicket;
use App\Models\TicketEscalation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EscalationService
{
    /**
     * Escalate a ticket to the next level.
     */
    public function escalate(SupportTicket $ticket, ?int $escalatedByUserId = null, ?string $reason = null): SupportTicket
    {
        return DB::transaction(function () use ($ticket, $escalatedByUserId, $reason) {
            $source = $ticket->source;
            $currentLevel = $ticket->escalation_level ?? 0;

            $nextRule = EscalationRule::nextLevelFor($source, $currentLevel);
            if (!$nextRule) {
                $this->logEscalation($ticket, null, null, null, $nextRule?->assign_to_role,
                    $currentLevel, 'escalated', $reason ?? 'Max escalation level reached');
                return $ticket;
            }

            $nextLevel = $nextRule->level;
            $assignToRole = $nextRule->assign_to_role;

            $assignee = $this->findUserByRole($assignToRole);

            $oldAssigneeId = $ticket->assigned_to;

            $ticket->update([
                'assigned_to' => $assignee?->id,
                'escalation_level' => $nextLevel,
                'escalated_at' => now(),
                'escalated_to_id' => $assignee?->id,
            ]);

            $this->logEscalation(
                $ticket,
                $escalatedByUserId,
                $oldAssigneeId,
                $assignee?->id,
                $assignToRole,
                $nextLevel,
                'escalated',
                $reason
            );

            return $ticket->fresh()->load(['assignee', 'escalatedTo']);
        });
    }

    /**
     * Resolve an escalated ticket.
     */
    public function resolve(SupportTicket $ticket, ?int $resolvedByUserId = null, ?string $note = null): SupportTicket
    {
        return DB::transaction(function () use ($ticket, $resolvedByUserId, $note) {
            $level = $ticket->escalation_level;

            $this->logEscalation(
                $ticket,
                $resolvedByUserId,
                $ticket->assigned_to,
                null,
                null,
                $level,
                'resolved',
                $note
            );

            $ticket->update([
                'status' => SupportTicket::STATUS_RESOLVED,
                'resolved_at' => now(),
                'escalation_level' => null,
                'escalated_at' => null,
                'escalated_to_id' => null,
            ]);

            return $ticket->fresh();
        });
    }

    /**
     * Check which escalated tickets are overdue for the next level.
     * Called by scheduled command.
     */
    public function processAutoEscalations(): array
    {
        $processed = [];

        $escalatedTickets = SupportTicket::whereIn('status', ['open', 'in_progress', 'waiting'])
            ->whereIn('source', ['auto_route_deviation', 'auto_delay', 'auto_fuel_flag'])
            ->whereNotNull('escalation_level')
            ->whereNotNull('escalated_at')
            ->get();

        foreach ($escalatedTickets as $ticket) {
            $rule = EscalationRule::getRuleFor($ticket->source, $ticket->escalation_level);
            if (!$rule) {
                continue;
            }

            $escalatedAt = $ticket->escalated_at;
            $threshold = $escalatedAt->copy()->addMinutes($rule->escalate_after_minutes);

            if (now()->greaterThanOrEqualTo($threshold)) {
                $this->escalate($ticket, null, "Auto-escalated: unresolved after {$rule->escalate_after_minutes} minutes");
                $processed[] = $ticket->id;
            }
        }

        return $processed;
    }

    /**
     * Get route alerts for a specific dispatcher.
     */
    public function getRouteAlertsForDispatcher(int $dispatcherUserId, array $filters = []): array
    {
        $query = SupportTicket::routeAlertsForDispatcher($dispatcherUserId)
            ->with(['user:id,name', 'category:id,name', 'assignee:id,name', 'subject']);

        if (!empty($filters['source'])) {
            $query->where('source', $filters['source']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $tickets = $query->latest()->get();

        $unassigned = SupportTicket::unassignedAlerts()
            ->with(['user:id,name', 'category:id,name', 'assignee:id,name', 'subject'])
            ->latest()
            ->get();

        return [
            'my_alerts' => $tickets,
            'unassigned' => $unassigned,
        ];
    }

    /**
     * Get route alert counts for sidebar badge.
     */
    public function getRouteAlertStats(): array
    {
        $open = SupportTicket::whereIn('source', ['auto_route_deviation', 'auto_delay', 'auto_fuel_flag'])
            ->whereIn('status', ['open', 'in_progress', 'waiting'])
            ->count();

        $escalated = SupportTicket::whereIn('source', ['auto_route_deviation', 'auto_delay', 'auto_fuel_flag'])
            ->whereIn('status', ['open', 'in_progress', 'waiting'])
            ->whereNotNull('escalation_level')
            ->count();

        return [
            'open_alerts' => $open,
            'escalated' => $escalated,
        ];
    }

    private function logEscalation(
        SupportTicket $ticket,
        ?int $fromUserId,
        ?int $fromAssigneeId,
        ?int $toUserId,
        ?string $toRole,
        int $level,
        string $action,
        ?string $reason = null
    ): TicketEscalation {
        $fromUser = $fromUserId ? User::find($fromUserId) : null;

        return TicketEscalation::create([
            'support_ticket_id' => $ticket->id,
            'from_user_id' => $fromUserId,
            'to_user_id' => $toUserId,
            'from_role' => $fromUser?->getRoleNames()->first(),
            'to_role' => $toRole,
            'level' => $level,
            'action' => $action,
            'reason' => $reason,
        ]);
    }

    private function findUserByRole(string $roleName): ?User
    {
        return User::role($roleName)->inRandomOrder()->first();
    }
}
