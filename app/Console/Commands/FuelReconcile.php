<?php

namespace App\Console\Commands;

use App\Models\Vehicle;
use App\Services\FuelManagementService;
use App\Services\SupportAutoTicketService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class FuelReconcile extends Command
{
    protected $signature = 'fuel:reconcile
        {--since= : Start date (Y-m-d). Defaults to 30 days ago.}
        {--until= : End date (Y-m-d). Defaults to today.}
        {--no-ticket : Do not auto-open support tickets on material variance.}';

    protected $description = 'Compare Wialon-detected fuel fills against recorded dispenses per vehicle/tank and open support tickets on material variance';

    public function handle(FuelManagementService $fuelService, SupportAutoTicketService $tickets): int
    {
        $since = $this->option('since') ? Carbon::parse($this->option('since'))->startOfDay() : now()->subDays(30);
        $until = $this->option('until') ? Carbon::parse($this->option('until'))->endOfDay() : now();

        $report = $fuelService->reconcileFuel($since, $until);

        $flagged = collect($report['vehicles'])->filter(fn ($r) => in_array($r['flag'], ['material', 'caution', 'override_heavy']));
        $material = collect($report['vehicles'])->filter(fn ($r) => $r['escalate']);

        $this->info("Reconciling Wialon fills vs recorded dispenses ({$since->format('Y-m-d')} → {$until->format('Y-m-d')}).");

        $this->table(
            ['Plate', 'Refills (L)', 'Recorded (L)', 'Variance (L)', 'Variance %', 'Overrides', 'Flag'],
            $report['vehicles']->map(fn ($r) => [
                $r['plate_number'],
                $r['wialon_refills'],
                $r['recorded_dispenses'],
                $r['variance_liters'],
                $r['variance_percent'],
                $r['override_count'],
                $r['flag'],
            ])->toArray()
        );

        $this->table(
            ['Tank', 'Variance (L)', 'Variance %', 'Flag'],
            $report['tanks']->map(fn ($t) => [$t['name'], $t['variance_liters'], $t['variance_percent'], $t['flag']])->toArray()
        );

        if ($flagged->isEmpty()) {
            $this->info('No anomalies detected.');
            return Command::SUCCESS;
        }

        $this->warn($flagged->count() . ' flagged row(s), ' . $material->count() . ' material.');

        if ($this->option('no-ticket')) {
            $this->info('Ticket auto-open skipped (--no-ticket).');
            return Command::SUCCESS;
        }

        $opened = 0;
        foreach ($material as $row) {
            $vehicle = Vehicle::find($row['vehicle_id']);
            $ticket = $tickets->open(
                [
                    'title' => "Material fuel variance — {$row['plate_number']}",
                    'description' => $this->describeVariance($row),
                    'priority' => abs($row['variance_liters']) >= 100 ? 'high' : 'normal',
                    'source' => 'auto_fuel_flag',
                ],
                $vehicle,
                'Fuel'
            );
            $opened = $ticket->wasRecentlyCreated ? $opened + 1 : $opened;
        }

        $this->info("Opened {$opened} new fuel variance ticket(s).");
        return Command::SUCCESS;
    }

    private function describeVariance(array $row): string
    {
        $direction = $row['variance_liters'] >= 0 ? 'Wialon detected more fuel being added than was recorded as dispensed' : 'recorded dispenses exceed Wialon-detected fills';

        return sprintf(
            "Auto-generated during fuel reconciliation.\n\n%s.\n\nPlate: %s\nPeriod refills (Wialon): %.2f L\nPeriod recorded dispenses: %.2f L\nVariance: %.2f L (%.2f%%)\nOverrides: %d (%.2f L)\n",
            $direction,
            $row['plate_number'],
            $row['wialon_refills'],
            $row['recorded_dispenses'],
            $row['variance_liters'],
            $row['variance_percent'],
            $row['override_count'],
            $row['override_liters']
        );
    }
}