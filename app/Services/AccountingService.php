<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Expense;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccountingService
{
    public function generateReference(): string
    {
        $year = now()->year;
        $count = JournalEntry::whereYear('created_at', $year)->count() + 1;
        return 'GL-' . $year . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
    }

    public function getAccountsTree(): array
    {
        $accounts = ChartOfAccount::whereNull('parent_id')
            ->with(['children' => function ($q) {
                $q->orderBy('code');
            }])
            ->orderBy('code')
            ->get()
            ->toArray();

        $types = ['asset', 'liability', 'equity', 'revenue', 'expense'];
        $grouped = [];
        foreach ($types as $type) {
            $grouped[$type] = array_values(array_filter($accounts, fn($a) => $a['type'] === $type));
        }
        return $grouped;
    }

    public function createEntry(array $data, ?int $userId = null): JournalEntry
    {
        return DB::transaction(function () use ($data, $userId) {
            $lines = $data['lines'] ?? [];
            unset($data['lines']);

            $totalDebit = collect($lines)->sum('debit');
            $totalCredit = collect($lines)->sum('credit');

            if (abs($totalDebit - $totalCredit) > 0.01) {
                throw ValidationException::withMessages([
                    'lines' => 'Total debits (' . number_format($totalDebit, 2) . ') must equal total credits (' . number_format($totalCredit, 2) . '). Difference: ' . number_format(abs($totalDebit - $totalCredit), 2),
                ]);
            }

            if (empty($lines)) {
                throw ValidationException::withMessages([
                    'lines' => 'At least one line item is required.',
                ]);
            }

            $data['reference'] = $data['reference'] ?? $this->generateReference();
            $data['created_by'] = $data['created_by'] ?? $userId;

            $entry = JournalEntry::create($data);

            foreach ($lines as $line) {
                $entry->lines()->create($line);
            }

            return $entry->load(['lines.account', 'createdBy', 'fiscalYear']);
        });
    }

    public function updateEntry(JournalEntry $entry, array $data): JournalEntry
    {
        if ($entry->status !== 'draft') {
            throw ValidationException::withMessages([
                'status' => 'Only draft entries can be updated.',
            ]);
        }

        return DB::transaction(function () use ($entry, $data) {
            $lines = $data['lines'] ?? null;
            unset($data['lines']);

            $entry->update($data);

            if ($lines !== null) {
                $totalDebit = collect($lines)->sum('debit');
                $totalCredit = collect($lines)->sum('credit');

                if (abs($totalDebit - $totalCredit) > 0.01) {
                    throw ValidationException::withMessages([
                        'lines' => 'Total debits must equal total credits.',
                    ]);
                }

                $entry->lines()->delete();
                foreach ($lines as $line) {
                    $entry->lines()->create($line);
                }
            }

            return $entry->load(['lines.account', 'createdBy', 'fiscalYear']);
        });
    }

    public function postEntry(JournalEntry $entry, ?int $userId = null): JournalEntry
    {
        if ($entry->status !== 'draft') {
            throw ValidationException::withMessages([
                'status' => 'Only draft entries can be posted.',
            ]);
        }

        return DB::transaction(function () use ($entry, $userId) {
            $entry->update([
                'status' => 'posted',
                'created_by' => $entry->created_by ?? $userId,
            ]);

            return $entry->fresh()->load(['lines.account', 'createdBy', 'fiscalYear']);
        });
    }

    public function reverseEntry(JournalEntry $entry, string $reason, ?int $userId = null): JournalEntry
    {
        if ($entry->status !== 'posted') {
            throw ValidationException::withMessages([
                'status' => 'Only posted entries can be reversed.',
            ]);
        }

        return DB::transaction(function () use ($entry, $reason, $userId) {
            $reversal = JournalEntry::create([
                'reference' => $this->generateReference(),
                'description' => 'Reversal: ' . $entry->reference . ' — ' . $reason,
                'date' => now(),
                'type' => $entry->type,
                'fiscal_year_id' => $entry->fiscal_year_id,
                'status' => 'posted',
                'created_by' => $userId,
                'source_type' => $entry->source_type,
                'source_id' => $entry->source_id,
            ]);

            foreach ($entry->lines as $line) {
                $reversal->lines()->create([
                    'account_id' => $line->account_id,
                    'debit' => $line->credit,
                    'credit' => $line->debit,
                    'notes' => $reason,
                ]);
            }

            $entry->update([
                'status' => 'reversed',
                'reversed_by' => $userId,
                'reversed_at' => now(),
                'reversal_entry_id' => $reversal->id,
            ]);

            return $entry->fresh()->load(['lines.account', 'createdBy', 'fiscalYear', 'reversalEntry']);
        });
    }

    public function trialBalance(?int $fiscalYearId = null, ?string $endDate = null): array
    {
        $query = JournalEntryLine::select(
            'chart_of_accounts.id as account_id',
            'chart_of_accounts.code',
            'chart_of_accounts.name',
            'chart_of_accounts.type',
            DB::raw('COALESCE(SUM(journal_entry_lines.debit), 0) as total_debit'),
            DB::raw('COALESCE(SUM(journal_entry_lines.credit), 0) as total_credit')
        )
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->join('chart_of_accounts', 'journal_entry_lines.account_id', '=', 'chart_of_accounts.id')
            ->where('journal_entries.status', 'posted');

        if ($endDate) {
            $query->where('journal_entries.date', '<=', $endDate);
        }

        if ($fiscalYearId) {
            $query->where('journal_entries.fiscal_year_id', $fiscalYearId);
        }

        $rows = $query->groupBy(
            'chart_of_accounts.id',
            'chart_of_accounts.code',
            'chart_of_accounts.name',
            'chart_of_accounts.type'
        )
            ->orderBy('chart_of_accounts.code')
            ->get()
            ->toArray();

        $totals = ['debit' => 0, 'credit' => 0];
        foreach ($rows as &$row) {
            $row['balance'] = $row['total_debit'] - $row['total_credit'];
            $totals['debit'] += $row['total_debit'];
            $totals['credit'] += $row['total_credit'];
        }

        return [
            'rows' => $rows,
            'totals' => $totals,
            'generated_at' => now()->toDateTimeString(),
        ];
    }

    public function profitLoss(?int $fiscalYearId = null, ?string $endDate = null): array
    {
        $data = $this->trialBalance($fiscalYearId, $endDate);
        $revenue = array_values(array_filter($data['rows'], fn($r) => $r['type'] === 'revenue'));
        $expenses = array_values(array_filter($data['rows'], fn($r) => $r['type'] === 'expense'));

        $totalRevenue = collect($revenue)->sum('balance');
        $totalExpenses = collect($expenses)->sum('balance');
        $netIncome = $totalRevenue - $totalExpenses;

        return [
            'revenue' => ['accounts' => $revenue, 'total' => abs($totalRevenue)],
            'expenses' => ['accounts' => $expenses, 'total' => abs($totalExpenses)],
            'net_income' => $netIncome,
            'generated_at' => now()->toDateTimeString(),
        ];
    }

    public function balanceSheet(?int $fiscalYearId = null, ?string $endDate = null): array
    {
        $data = $this->trialBalance($fiscalYearId, $endDate);
        $assets = array_values(array_filter($data['rows'], fn($r) => $r['type'] === 'asset'));
        $liabilities = array_values(array_filter($data['rows'], fn($r) => $r['type'] === 'liability'));
        $equity = array_values(array_filter($data['rows'], fn($r) => $r['type'] === 'equity'));

        $totalAssets = collect($assets)->sum('balance');
        $totalLiabilities = collect($liabilities)->sum('balance');
        $totalEquity = collect($equity)->sum('balance');

        $pl = $this->profitLoss($fiscalYearId, $endDate);
        $retainedEarnings = $pl['net_income'];

        $totalLiabilitiesEquity = $totalLiabilities + $totalEquity + $retainedEarnings;

        return [
            'assets' => ['accounts' => $assets, 'total' => $totalAssets],
            'liabilities' => ['accounts' => $liabilities, 'total' => $totalLiabilities],
            'equity' => ['accounts' => $equity, 'total' => $totalEquity],
            'retained_earnings' => $retainedEarnings,
            'total_liabilities_equity' => $totalLiabilitiesEquity,
            'generated_at' => now()->toDateTimeString(),
        ];
    }

    public function autoPostInvoice(Invoice $invoice): ?JournalEntry
    {
        if ($invoice->status !== 'sent') return null;

        return DB::transaction(function () use ($invoice) {
            $revenueAccount = ChartOfAccount::where('type', 'revenue')->where('code', '4000')->first();
            $arAccount = ChartOfAccount::where('type', 'asset')->where('code', '1200')->first();

            if (!$revenueAccount || !$arAccount) return null;

            return $this->createEntry([
                'description' => 'Invoice ' . $invoice->reference,
                'date' => $invoice->issue_date,
                'type' => 'auto_invoice',
                'source_type' => 'App\\Models\\Invoice',
                'source_id' => $invoice->id,
                'status' => 'posted',
                'lines' => [
                    ['account_id' => $arAccount->id, 'debit' => $invoice->total, 'credit' => 0, 'notes' => 'Accounts Receivable'],
                    ['account_id' => $revenueAccount->id, 'debit' => 0, 'credit' => $invoice->total, 'notes' => 'Revenue'],
                ],
            ]);
        });
    }

    public function autoPostPayment(Payment $payment): ?JournalEntry
    {
        if (!$payment->invoice_id) return null;

        return DB::transaction(function () use ($payment) {
            $cashAccount = ChartOfAccount::where('type', 'asset')->where('code', '1100')->first();
            $arAccount = ChartOfAccount::where('type', 'asset')->where('code', '1200')->first();

            if (!$cashAccount || !$arAccount) return null;

            return $this->createEntry([
                'description' => 'Payment ' . ($payment->tx_reference ?? $payment->id) . ' for Invoice',
                'date' => $payment->paid_at ?? now(),
                'type' => 'auto_payment',
                'source_type' => 'App\\Models\\Payment',
                'source_id' => $payment->id,
                'status' => 'posted',
                'lines' => [
                    ['account_id' => $cashAccount->id, 'debit' => $payment->amount, 'credit' => 0, 'notes' => 'Cash/Bank'],
                    ['account_id' => $arAccount->id, 'debit' => 0, 'credit' => $payment->amount, 'notes' => 'Accounts Receivable'],
                ],
            ]);
        });
    }

    public function autoPostExpense(Expense $expense): ?JournalEntry
    {
        if ($expense->status !== 'paid') return null;

        return DB::transaction(function () use ($expense) {
            $expenseAccount = ChartOfAccount::where('type', 'expense')->where('code', '5000')->first();
            $cashAccount = ChartOfAccount::where('type', 'asset')->where('code', '1100')->first();

            if (!$expenseAccount || !$cashAccount) return null;

            return $this->createEntry([
                'description' => 'Expense ' . $expense->reference,
                'date' => $expense->paid_at ?? now(),
                'type' => 'auto_expense',
                'source_type' => 'App\\Models\\Expense',
                'source_id' => $expense->id,
                'status' => 'posted',
                'lines' => [
                    ['account_id' => $expenseAccount->id, 'debit' => $expense->amount, 'credit' => 0, 'notes' => $expense->name],
                    ['account_id' => $cashAccount->id, 'debit' => 0, 'credit' => $expense->amount, 'notes' => 'Cash/Bank'],
                ],
            ]);
        });
    }
}
