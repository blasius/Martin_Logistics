<?php

namespace App\Console\Commands;

use App\Models\SupportTicket;
use App\Models\VehicleInspection;
use App\Models\VehicleInsurance;
use App\Services\DocumentService;
use App\Services\SupportAutoTicketService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckExpiringDocuments extends Command
{
    protected $signature = 'documents:check-expiry {--days=30 : Days threshold for expiry warning}';
    protected $description = 'Check documents, insurance and inspections expiring soon and open auto-tickets';

    public function handle(DocumentService $documentService, SupportAutoTicketService $autoTickets): int
    {
        $days = (int) $this->option('days');
        $threshold = now()->addDays($days)->toDateString();
        $opened = 0;

        // Uploaded documents (morph: vehicle, driver, ...)
        $expiring = $documentService->getExpiringDocuments($days);

        foreach ($expiring as $document) {
            $this->line("  - {$document->name} expires {$document->expires_at->format('Y-m-d')}");
            $document->update(['expiry_reminder_sent_at' => now()]);

            $ticket = $autoTickets->open([
                'source' => 'auto_document_expiry',
                'title' => "Document expiring: {$document->name}",
                'description' => "{$document->name} expires on {$document->expires_at->format('Y-m-d')}.",
                'priority' => SupportTicket::PRIORITY_NORMAL,
            ], $document->documentable, 'Documents');

            $opened += $ticket->wasRecentlyCreated ? 1 : 0;
        }

        $this->info('Documents expiring within ' . $days . ' day(s): ' . $expiring->count());

        // Vehicle insurance
        $insurances = VehicleInsurance::whereNotNull('expiry_date')
            ->where('expiry_date', '<=', $threshold)
            ->with('vehicle')
            ->get();

        foreach ($insurances as $insurance) {
            if (!$insurance->vehicle) {
                continue;
            }

            if ($autoTickets->hasOpenTicket('auto_document_expiry', $insurance->vehicle, null, 365)) {
                continue;
            }

            $expiry = Carbon::parse($insurance->expiry_date);
            $expired = $expiry->isPast();
            $plate = $insurance->vehicle->plate_number;
            $label = $expired ? 'Insurance expired' : 'Insurance expiring';

            $this->line("  - {$label}: {$plate} ({$expiry->format('Y-m-d')})");

            $ticket = $autoTickets->open([
                'source' => 'auto_document_expiry',
                'title' => "{$label}: {$plate}",
                'description' => sprintf(
                    'Vehicle %s insurance policy %s %s on %s.',
                    $plate,
                    $insurance->policy_number ?: 'n/a',
                    $expired ? 'expired' : 'expires',
                    $expiry->format('Y-m-d')
                ),
                'priority' => $expired ? SupportTicket::PRIORITY_HIGH : SupportTicket::PRIORITY_NORMAL,
            ], $insurance->vehicle, 'Documents');

            $opened += $ticket->wasRecentlyCreated ? 1 : 0;
        }

        // Vehicle inspections that are scheduled (or overdue) and not completed
        $inspections = VehicleInspection::whereNull('completed_date')
            ->whereNotNull('scheduled_date')
            ->where('scheduled_date', '<=', $threshold)
            ->with('vehicle')
            ->get();

        foreach ($inspections as $inspection) {
            if (!$inspection->vehicle) {
                continue;
            }

            if ($autoTickets->hasOpenTicket('auto_document_expiry', $inspection->vehicle, null, 365)) {
                continue;
            }

            $scheduled = Carbon::parse($inspection->scheduled_date);
            $overdue = $scheduled->isPast();
            $plate = $inspection->vehicle->plate_number;
            $label = $overdue ? 'Inspection overdue' : 'Inspection due';

            $this->line("  - {$label}: {$plate} ({$scheduled->format('Y-m-d')})");

            $ticket = $autoTickets->open([
                'source' => 'auto_document_expiry',
                'title' => "{$label}: {$plate}",
                'description' => sprintf(
                    'Vehicle %s inspection %s was scheduled for %s.',
                    $plate,
                    $overdue ? 'is overdue' : 'is due',
                    $scheduled->format('Y-m-d')
                ),
                'priority' => $overdue ? SupportTicket::PRIORITY_HIGH : SupportTicket::PRIORITY_NORMAL,
            ], $inspection->vehicle, 'Documents');

            $opened += $ticket->wasRecentlyCreated ? 1 : 0;
        }

        $this->info("Opened {$opened} document-expiry auto-ticket(s).");

        return Command::SUCCESS;
    }
}
