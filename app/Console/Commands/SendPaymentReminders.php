<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Notifications\CustomerInvoiceNotification;
use Illuminate\Console\Command;

class SendPaymentReminders extends Command
{
    protected $signature = 'payments:send-reminders';
    protected $description = 'Send payment reminders for overdue invoices';

    public function handle(): int
    {
        $overdueInvoices = Invoice::whereIn('status', ['sent', 'overdue'])
            ->where('due_date', '<', now())
            ->whereDoesntHave('payments', fn ($q) => $q->where('status', 'completed'))
            ->get();

        if ($overdueInvoices->isEmpty()) {
            $this->info('No overdue invoices found.');
            return Command::SUCCESS;
        }

        $sent = 0;
        foreach ($overdueInvoices as $invoice) {
            $user = $invoice->client?->user;
            if (!$user) {
                continue;
            }

            $status = $invoice->due_date->diffInDays(now()) > 30 ? 'overdue' : 'sent';

            $user->notify(new CustomerInvoiceNotification($invoice, $status));
            $sent++;

            if ($invoice->status !== 'overdue') {
                $invoice->update(['status' => 'overdue']);
            }
        }

        $this->info("Sent {$sent} payment reminder(s).");
        return Command::SUCCESS;
    }
}
