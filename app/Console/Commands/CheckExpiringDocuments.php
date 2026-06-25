<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Services\DocumentService;
use Illuminate\Console\Command;

class CheckExpiringDocuments extends Command
{
    protected $signature = 'documents:check-expiry {--days=30 : Days threshold for expiry warning}';
    protected $description = 'Check for documents expiring soon and send notifications';

    public function handle(DocumentService $documentService): int
    {
        $days = (int) $this->option('days');
        $expiring = $documentService->getExpiringDocuments($days);

        if ($expiring->isEmpty()) {
            $this->info("No documents expiring within {$days} days.");
            return Command::SUCCESS;
        }

        $this->info("Found {$expiring->count()} document(s) expiring within {$days} days.");

        foreach ($expiring as $document) {
            $this->line("  - {$document->name} expires {$document->expires_at->format('Y-m-d')}");

            $document->update(['expiry_reminder_sent_at' => now()]);
        }

        return Command::SUCCESS;
    }
}
