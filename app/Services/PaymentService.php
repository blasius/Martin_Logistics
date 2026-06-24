<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function recordPayment(Invoice $invoice, array $data): Payment
    {
        return DB::transaction(function () use ($invoice, $data) {
            $payment = $invoice->payments()->create([
                'amount' => $data['amount'],
                'currency_id' => $invoice->currency_id,
                'method' => $data['method'] ?? null,
                'tx_reference' => $data['tx_reference'] ?? null,
                'paid_at' => $data['paid_at'] ?? now(),
                'notes' => $data['notes'] ?? null,
                'paid_by_user_id' => auth()->id(),
            ]);

            $paid = $invoice->payments()->sum('amount');
            if ($paid >= $invoice->total) {
                $invoice->update(['status' => 'paid']);
            }

            return $payment;
        });
    }

    public function getAgingReport(): array
    {
        $today = now()->startOfDay();
        $invoices = Invoice::with('client')
            ->whereIn('status', ['sent', 'overdue'])
            ->where('due_date', '<', $today)
            ->get();

        $buckets = [
            'current' => ['label' => '0-30 Days', 'invoices' => [], 'total' => 0],
            '31_60' => ['label' => '31-60 Days', 'invoices' => [], 'total' => 0],
            '61_90' => ['label' => '61-90 Days', 'invoices' => [], 'total' => 0],
            '90_plus' => ['label' => '90+ Days', 'invoices' => [], 'total' => 0],
        ];

        $grandTotal = 0;

        foreach ($invoices as $invoice) {
            $paid = $invoice->payments()->sum('amount');
            $balance = max(0, $invoice->total - $paid);
            if ($balance <= 0) continue;

            $daysOverdue = $today->diffInDays($invoice->due_date, false);

            $bucket = match (true) {
                $daysOverdue > 90 => '90_plus',
                $daysOverdue > 60 => '61_90',
                $daysOverdue > 30 => '31_60',
                default => 'current',
            };

            $buckets[$bucket]['invoices'][] = [
                'id' => $invoice->id,
                'reference' => $invoice->reference,
                'client_name' => $invoice->client?->name,
                'total' => $invoice->total,
                'balance' => $balance,
                'due_date' => $invoice->due_date->format('Y-m-d'),
                'days_overdue' => $daysOverdue,
            ];
            $buckets[$bucket]['total'] += $balance;
            $grandTotal += $balance;
        }

        return [
            'buckets' => $buckets,
            'grand_total' => $grandTotal,
        ];
    }

    public function getClientStatement(int $clientId): array
    {
        $invoices = Invoice::with(['client', 'items', 'payments'])
            ->where('client_id', $clientId)
            ->orderBy('issue_date')
            ->get();

        $balance = 0;
        $entries = [];

        foreach ($invoices as $invoice) {
            $paid = $invoice->payments()->sum('amount');
            $balance += $invoice->total - $paid;

            $entries[] = [
                'date' => $invoice->issue_date->format('Y-m-d'),
                'reference' => $invoice->reference,
                'type' => $invoice->type,
                'invoice_id' => $invoice->id,
                'debit' => in_array($invoice->type, ['invoice', 'debit_note']) ? $invoice->total : 0,
                'credit' => in_array($invoice->type, ['credit_note']) ? $invoice->total : 0,
                'paid' => $paid,
                'balance' => round($balance, 2),
                'due_date' => $invoice->due_date->format('Y-m-d'),
                'status' => $invoice->status,
            ];
        }

        return [
            'client' => $invoices->first()?->client,
            'entries' => $entries,
            'total_outstanding' => round($balance, 2),
        ];
    }
}
