<?php

namespace App\Console\Commands;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Services\AccountingService;
use Illuminate\Console\Command;

class AutoPostToGL extends Command
{
    protected $signature = 'accounting:auto-post {--dry-run : Preview without posting}';
    protected $description = 'Auto-post unposted invoices, payments, and expenses to General Ledger';

    public function handle(AccountingService $service): int
    {
        $dryRun = $this->option('dry-run');
        $counts = ['invoices' => 0, 'payments' => 0, 'expenses' => 0];

        $invoiceIdsWithEntries = JournalEntry::where('source_type', Invoice::class)
            ->where('status', 'posted')
            ->pluck('source_id')
            ->toArray();

        $invoices = Invoice::where('status', 'sent')
            ->whereNotIn('id', $invoiceIdsWithEntries)
            ->get();

        foreach ($invoices as $invoice) {
            $counts['invoices']++;
            if (!$dryRun) {
                $service->autoPostInvoice($invoice);
            }
        }

        $paymentIdsWithEntries = JournalEntry::where('source_type', Payment::class)
            ->where('status', 'posted')
            ->pluck('source_id')
            ->toArray();

        $payments = Payment::whereNotNull('invoice_id')
            ->whereNotIn('id', $paymentIdsWithEntries)
            ->get();

        foreach ($payments as $payment) {
            $counts['payments']++;
            if (!$dryRun) {
                $service->autoPostPayment($payment);
            }
        }

        $expenseIdsWithEntries = JournalEntry::where('source_type', Expense::class)
            ->where('status', 'posted')
            ->pluck('source_id')
            ->toArray();

        $expenses = Expense::where('status', 'paid')
            ->whereNotIn('id', $expenseIdsWithEntries)
            ->get();

        foreach ($expenses as $expense) {
            $counts['expenses']++;
            if (!$dryRun) {
                $service->autoPostExpense($expense);
            }
        }

        $this->table(
            ['Source', 'Count'],
            [
                ['Invoices', $counts['invoices']],
                ['Payments', $counts['payments']],
                ['Expenses', $counts['expenses']],
            ]
        );

        $total = array_sum($counts);
        if ($dryRun) {
            $this->info("Dry run: {$total} items would be posted.");
        } else {
            $this->info("Posted {$total} items to General Ledger.");
        }

        return Command::SUCCESS;
    }
}
