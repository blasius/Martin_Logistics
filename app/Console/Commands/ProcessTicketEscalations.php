<?php

namespace App\Console\Commands;

use App\Models\EscalationRule;
use App\Models\SupportTicket;
use App\Services\EscalationService;
use Illuminate\Console\Command;

class ProcessTicketEscalations extends Command
{
    protected $signature = 'tickets:process-escalations';
    protected $description = 'Ensure escalation rules exist, warn on orphans, then auto-escalate unresolved support tickets';

    public function handle(EscalationService $escalationService): int
    {
        // Rule completeness: idempotently install the default rule-set per
        // anomaly source and flag any escalated ticket missing a rule.
        EscalationRule::seedDefaults();

        $orphans = SupportTicket::whereIn('source', SupportTicket::AUTO_SOURCES)
            ->whereIn('status', ['open', 'in_progress', 'waiting'])
            ->whereNotNull('escalation_level')
            ->get()
            ->filter(fn (SupportTicket $ticket) => !EscalationRule::getRuleFor($ticket->source, $ticket->escalation_level))
            ->values();

        if ($orphans->isNotEmpty()) {
            $this->warn("Rule completeness: {$orphans->count()} escalated auto-ticket(s) have no matching escalation rule (source+level).");
            foreach ($orphans->take(5) as $ticket) {
                $this->warn("  - {$ticket->reference} source={$ticket->source} level={$ticket->escalation_level}");
            }
        } else {
            $this->line('Rule completeness: all escalated auto-tickets have matching rules.');
        }

        $processed = $escalationService->processAutoEscalations();

        $count = count($processed);
        if ($count > 0) {
            $this->info("Auto-escalated {$count} ticket(s).");
        } else {
            $this->info('No tickets needed escalation.');
        }

        return Command::SUCCESS;
    }
}