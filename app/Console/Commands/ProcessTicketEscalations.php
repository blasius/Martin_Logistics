<?php

namespace App\Console\Commands;

use App\Services\EscalationService;
use Illuminate\Console\Command;

class ProcessTicketEscalations extends Command
{
    protected $signature = 'tickets:process-escalations';
    protected $description = 'Auto-escalate unresolved support tickets based on escalation rules';

    public function handle(EscalationService $escalationService): int
    {
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
