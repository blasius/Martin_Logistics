<?php

namespace App\Services;

use App\Models\Contract;
use Carbon\Carbon;

class ContractService
{
    public function generateReference(): string
    {
        $year = now()->year;
        $count = Contract::whereYear('created_at', $year)->count() + 1;
        return 'CNT-' . $year . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
    }

    public function checkExpiry(): array
    {
        $now = now()->startOfDay();
        $warnings = [];

        $expiringSoon = Contract::where('status', 'active')
            ->whereDate('end_date', '>=', $now)
            ->whereDate('end_date', '<=', $now->copy()->addDays(30))
            ->get();

        foreach ($expiringSoon as $contract) {
            $daysLeft = $now->diffInDays($contract->end_date, false);
            $warnings[] = [
                'contract_id' => $contract->id,
                'reference' => $contract->reference,
                'client' => $contract->client->name,
                'end_date' => $contract->end_date->format('Y-m-d'),
                'days_left' => $daysLeft,
                'severity' => $daysLeft <= 7 ? 'critical' : ($daysLeft <= 14 ? 'warning' : 'info'),
            ];
        }

        return $warnings;
    }

    public function checkSla(): array
    {
        $breaches = [];

        $active = Contract::with('client')
            ->where('status', 'active')
            ->whereNotNull('sla_response_hours')
            ->get();

        // SLA breach detection relies on support tickets linked to orders under this contract.
        // This is a placeholder that returns contracts with SLA config for the frontend to use.
        // Real breach detection would join support_tickets via orders.

        foreach ($active as $contract) {
            $breaches[] = [
                'contract_id' => $contract->id,
                'reference' => $contract->reference,
                'client' => $contract->client->name,
                'sla_response_hours' => $contract->sla_response_hours,
                'sla_resolution_hours' => $contract->sla_resolution_hours,
            ];
        }

        return $breaches;
    }

    public function expirePast(): int
    {
        return Contract::where('status', 'active')
            ->whereDate('end_date', '<', now())
            ->update(['status' => 'expired']);
    }
}
