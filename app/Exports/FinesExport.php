<?php

namespace App\Exports;

use App\Models\TrafficFine;
use App\Models\Vehicle;
use App\Models\Trailer;
use Maatwebsite\Excel\Concerns\{FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, WithMultipleSheets, ShouldAutoSize};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\DB;

class FinesExport implements WithMultipleSheets
{
    protected $query;

    public function __construct($query) {
        $this->query = clone $query;
    }

    public function sheets(): array {
        return [
            new ActiveDebtSheet($this->query),
            new AnalyticsSheet($this->query)
        ];
    }
}

class ActiveDebtSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize
{
    protected $query;

    public function __construct($query) {
        $this->query = clone $query;
    }

    public function title(): string { return 'Operational Debt'; }

    public function collection() {
        // Instead of fetching ALL vehicles, we base it on the filtered TrafficFines query.
        // We get the unique IDs of Vehicles and Trailers from the filtered fines.
        // The status filter itself is owned by the controller, so this sheet
        // honours whatever population the caller asked for.

        $filteredFines = $this->query->get();

        $vehicleIds = $filteredFines->where('fineable_type', Vehicle::class)->pluck('fineable_id')->unique();
        $trailerIds = $filteredFines->where('fineable_type', Trailer::class)->pluck('fineable_id')->unique();

        // Get the vehicles directly requested
        $vehicles = Vehicle::whereIn('id', $vehicleIds)
            ->whereIn('status', ['active', 'maintenance'])
            ->get();

        // Get trailers directly requested and find their currently assigned vehicles
        $trailers = Trailer::whereIn('id', $trailerIds)
            ->whereIn('status', ['active', 'maintenance'])
            ->get();

        $vehicleIdsFromTrailers = DB::table('trailer_assignments')
            ->whereIn('trailer_id', $trailers->pluck('id'))
            ->whereNull('unassigned_at')
            ->pluck('vehicle_id')
            ->unique();

        $vehiclesFromTrailers = Vehicle::whereIn('id', $vehicleIdsFromTrailers)
            ->whereIn('status', ['active', 'maintenance'])
            ->get();

        // Merge to get a unique list of vehicles we need to process
        $allVehiclesToProcess = $vehicles->merge($vehiclesFromTrailers)->unique('id');

        // We also need to handle standalone trailers (trailers with fines but not currently attached to any active vehicle)
        $trailersAttachedToActiveVehicles = DB::table('trailer_assignments')
            ->whereIn('vehicle_id', $allVehiclesToProcess->pluck('id'))
            ->whereNull('unassigned_at')
            ->pluck('trailer_id')
            ->unique();

        $standaloneTrailers = $trailers->whereNotIn('id', $trailersAttachedToActiveVehicles);

        $exportData = collect();

        // 1. Process Vehicles (and their attached trailers)
        foreach ($allVehiclesToProcess as $vehicle) {
            $drivers = DB::table('users')
                ->join('driver_vehicle_assignments', 'users.id', '=', 'driver_vehicle_assignments.driver_id')
                ->where('vehicle_id', $vehicle->id)
                ->whereNull('end_date')
                ->pluck('name')
                ->implode(', ');
            $driverName = $drivers ?: 'No Driver';

            $linkedTrailer = DB::table('trailers')
                ->join('trailer_assignments', 'trailers.id', '=', 'trailer_assignments.trailer_id')
                ->where('vehicle_id', $vehicle->id)
                ->whereNull('unassigned_at')
                ->first();

            // Check if the linked trailer is active/maintenance. If not, we ignore its fines for this export.
            $isActiveTrailer = false;
            if ($linkedTrailer) {
                 $isActiveTrailer = Trailer::where('id', $linkedTrailer->trailer_id)
                    ->whereIn('status', ['active', 'maintenance'])
                    ->exists();
            }

            $trailerPlate = ($linkedTrailer && $isActiveTrailer) ? $linkedTrailer->plate_number : 'None';
            $plateDisplay = $trailerPlate !== 'None' ? "{$vehicle->plate_number} / {$trailerPlate}" : $vehicle->plate_number;

            // Fines
            // Base queries off the ORIGINAL filtered query to respect user filters (like plate search)
            $vehicleFinesQuery = clone $this->query;
            $vehicleFines = $vehicleFinesQuery
                ->where('fineable_type', Vehicle::class)
                ->where('fineable_id', $vehicle->id)
                ->with('violations')
                ->get();

            $trailerFines = collect();
            if ($linkedTrailer && $isActiveTrailer) {
                 $trailerFinesQuery = clone $this->query;
                 $trailerFines = $trailerFinesQuery
                    ->where('fineable_type', Trailer::class)
                    ->where('fineable_id', $linkedTrailer->trailer_id)
                    ->with('violations')
                    ->get();
            }

            $allFines = $vehicleFines->merge($trailerFines);

            if ($allFines->isNotEmpty()) {
                $exportData->push($this->buildRow($allFines, $plateDisplay, strtoupper($vehicle->status), $driverName));
            }
        }

// 2. Process Standalone Trailers (trailers with fines, active/maintenance, but not attached)
        foreach ($standaloneTrailers as $trailer) {
             $trailerFinesQuery = clone $this->query;
             $trailerFines = $trailerFinesQuery
                ->where('fineable_type', Trailer::class)
                ->where('fineable_id', $trailer->id)
                ->with('violations')
                ->get();

            if ($trailerFines->isNotEmpty()) {
                $exportData->push($this->buildRow(
                    $trailerFines,
                    "None / {$trailer->plate_number}",
                    strtoupper($trailer->status),
                    'No Driver (Unattached)'
                ));
            }
        }

        return $exportData;
    }

    /**
     * Build one aggregated row for a unit.
     *
     * Urgency comes from the portal's Pay By date rather than any assumed
     * payment window: a unit is OVERDUE once any of its fines is past its
     * Pay By date, DUE SOON when the nearest one falls due inside the warning
     * window, and DUE otherwise. Amounts report principal and late fee
     * separately, with the total owed being the sum of the two.
     */
    protected function buildRow($fines, string $plateDisplay, string $unitStatus, string $driverName): array
    {
        $principal = round((float) $fines->sum('ticket_amount'), 2);
        $lateFees = round((float) $fines->sum('late_fee'), 2);

        $worst = $fines
            ->filter(fn ($f) => $f->pay_by !== null)
            ->sortBy(fn ($f) => $f->pay_by->getTimestamp())
            ->first();

        $urgency = $worst ? $worst->dueStateLabel() : TrafficFine::DUE_STATE_UNKNOWN;
        $nextPayBy = $worst?->pay_by?->toDateString();
        $daysUntilPayBy = $worst?->daysUntilPayBy();

        $descriptions = $fines->flatMap(function ($fine) {
            $prefix = $fine->fineable_type === Vehicle::class ? '[Vehicle] ' : '[Trailer] ';
            return $fine->violations->map(function ($violation) use ($prefix) {
                return $prefix . $violation->violation_name;
            });
        })->implode(' | ');

        return [
            'urgency' => $urgency,
            'plateDisplay' => $plateDisplay,
            'status' => $unitStatus,
            'driverName' => $driverName,
            'principal' => $principal,
            'lateFee' => $lateFees,
            'totalOwed' => round($principal + $lateFees, 2),
            'nextPayBy' => $nextPayBy,
            'daysUntilPayBy' => $daysUntilPayBy,
            'descriptions' => $descriptions,
        ];
    }

    public function headings(): array {
        return [
            'Urgency',
            'Plate (Vehicle/Trailer)',
            'Unit Status',
            'Current Driver(s)',
            'Principal (FRW)',
            'Late Fees (FRW)',
            'Total Owed (FRW)',
            'Next Pay By',
            'Days Until Pay By',
            'Offense Details'
        ];
    }

    public function map($row): array {
        return [
            $row['urgency'],
            $row['plateDisplay'],
            $row['status'],
            $row['driverName'],
            $row['principal'],
            $row['lateFee'],
            $row['totalOwed'],
            $row['nextPayBy'],
            $row['daysUntilPayBy'],
            $row['descriptions']
        ];
    }

    public function styles(Worksheet $sheet) {
        $sheet->setAutoFilter('A1:J1');
        $sheet->getStyle('A1:J1')->getFont()->setBold(true);

        $overdue = TrafficFine::DUE_STATE_OVERDUE;
        $dueSoon = TrafficFine::DUE_STATE_DUE_SOON;

        foreach ($sheet->getRowIterator(2) as $row) {
            $rowIndex = $row->getRowIndex();
            $urgency = (string) $sheet->getCell('A' . $rowIndex)->getValue();

            if ($urgency === strtoupper($overdue)) {
                $sheet->getStyle("A{$rowIndex}:J{$rowIndex}")->applyFromArray([
                    'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'FFC7CE']],
                    'font' => ['color' => ['rgb' => '9C0006']]
                ]);
            } elseif ($urgency === strtoupper($dueSoon)) {
                $sheet->getStyle("A{$rowIndex}:J{$rowIndex}")->applyFromArray([
                    'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'FFEB9C']],
                    'font' => ['color' => ['rgb' => '9C6500']]
                ]);
            }
        }
    }
}

class AnalyticsSheet implements WithTitle, WithStyles {
    protected $query;
    public function __construct($query) {
        $this->query = clone $query;
    }
    public function title(): string { return 'Management Charts'; }

    public function styles(Worksheet $sheet) {
        // Apply the same active/maintenance filter as the debt sheet so the
        // two sheets always agree on the population being reported.
        $filteredFines = $this->query->get();

        $vehicleIds = $filteredFines->where('fineable_type', Vehicle::class)->pluck('fineable_id');
        $trailerIds = $filteredFines->where('fineable_type', Trailer::class)->pluck('fineable_id');

        $activeVehicleIds = Vehicle::whereIn('id', $vehicleIds)->whereIn('status', ['active', 'maintenance'])->pluck('id');
        $activeTrailerIds = Trailer::whereIn('id', $trailerIds)->whereIn('status', ['active', 'maintenance'])->pluck('id');

        $validFines = $filteredFines->filter(function($fine) use ($activeVehicleIds, $activeTrailerIds) {
            if ($fine->fineable_type === Vehicle::class) {
                return $activeVehicleIds->contains($fine->fineable_id);
            }
            if ($fine->fineable_type === Trailer::class) {
                return $activeTrailerIds->contains($fine->fineable_id);
            }
            return false;
        });

        $principal = round((float) $validFines->sum('ticket_amount'), 2);
        $lateFees = round((float) $validFines->sum('late_fee'), 2);

        // Header
        $sheet->setCellValue('A1', 'DEBT INSIGHTS');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);

        // Summary Table
        $sheet->setCellValue('A3', 'Metric');
        $sheet->setCellValue('B3', 'Value');
        $sheet->getStyle('A3:B3')->getFont()->setBold(true);

        // Overdue is counted straight off the portal Pay By date.
        $overdueCount = $validFines->filter(fn ($f) => $f->isOverdue())->count();
        $dueSoonCount = $validFines->filter(fn ($f) => $f->dueState() === TrafficFine::DUE_STATE_DUE_SOON)->count();
        $penalizedCount = $validFines->filter(fn ($f) => (float) $f->late_fee > 0)->count();

        $rows = [
            ['Outstanding Principal', $principal . ' FRW'],
            ['Accrued Late Fees', $lateFees . ' FRW'],
            ['Total Outstanding Debt', ($principal + $lateFees) . ' FRW'],
            ['Overdue (past Pay By date)', $overdueCount],
            ['Due Soon (within ' . TrafficFine::DUE_SOON_DAYS . ' days of Pay By)', $dueSoonCount],
            ['Carrying a Late Fee', $penalizedCount],
            ['Outstanding Tickets', $validFines->count()],
        ];

        foreach ($rows as $i => [$label, $value]) {
            $sheet->setCellValue('A' . (4 + $i), $label);
            $sheet->setCellValue('B' . (4 + $i), $value);
        }

        // This allows the manager to select A3:B10 and click "Insert Chart" in Excel
        $sheet->getColumnDimension('A')->setWidth(38);
    }
}
