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

        $filteredFines = $this->query->where('status', 'PENDING')->get();

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
            $vehicleFines = $vehicleFinesQuery->where('status', 'PENDING')
                ->where('fineable_type', Vehicle::class)
                ->where('fineable_id', $vehicle->id)
                ->with('violations')
                ->get();

            $trailerFines = collect();
            if ($linkedTrailer && $isActiveTrailer) {
                 $trailerFinesQuery = clone $this->query;
                 $trailerFines = $trailerFinesQuery->where('status', 'PENDING')
                    ->where('fineable_type', Trailer::class)
                    ->where('fineable_id', $linkedTrailer->trailer_id)
                    ->with('violations')
                    ->get();
            }

            $allFines = $vehicleFines->merge($trailerFines);

            if ($allFines->isNotEmpty()) {
                $totalAmount = $allFines->sum('ticket_amount');
                $oldestFineDate = $allFines->min('issued_at');
                $days = $oldestFineDate ? \Carbon\Carbon::parse($oldestFineDate)->diffInDays(now()) : 0;

                $descriptions = $allFines->flatMap(function ($fine) {
                    $prefix = $fine->fineable_type === Vehicle::class ? '[Vehicle] ' : '[Trailer] ';
                    return $fine->violations->map(function ($violation) use ($prefix) {
                        return $prefix . $violation->violation_name;
                    });
                })->implode(' | ');

                $exportData->push([
                    'urgency' => $days > 14 ? 'CRITICAL' : 'PENDING',
                    'plateDisplay' => $plateDisplay,
                    'status' => strtoupper($vehicle->status),
                    'driverName' => $driverName,
                    'totalAmount' => $totalAmount,
                    'days' => $days,
                    'descriptions' => $descriptions
                ]);
            }
        }

        // 2. Process Standalone Trailers (trailers with fines, active/maintenance, but not attached)
        foreach ($standaloneTrailers as $trailer) {
             $trailerFinesQuery = clone $this->query;
             $trailerFines = $trailerFinesQuery->where('status', 'PENDING')
                ->where('fineable_type', Trailer::class)
                ->where('fineable_id', $trailer->id)
                ->with('violations')
                ->get();

            if ($trailerFines->isNotEmpty()) {
                 $totalAmount = $trailerFines->sum('ticket_amount');
                 $oldestFineDate = $trailerFines->min('issued_at');
                 $days = $oldestFineDate ? \Carbon\Carbon::parse($oldestFineDate)->diffInDays(now()) : 0;

                 $descriptions = $trailerFines->flatMap(function ($fine) {
                    return $fine->violations->map(function ($violation) {
                        return '[Trailer] ' . $violation->violation_name;
                    });
                })->implode(' | ');

                $exportData->push([
                    'urgency' => $days > 14 ? 'CRITICAL' : 'PENDING',
                    'plateDisplay' => "None / {$trailer->plate_number}",
                    'status' => strtoupper($trailer->status),
                    'driverName' => 'No Driver (Unattached)',
                    'totalAmount' => $totalAmount,
                    'days' => $days,
                    'descriptions' => $descriptions
                ]);
            }
        }

        return $exportData;
    }

    public function headings(): array {
        return [
            'Urgency',
            'Plate (Vehicle/Trailer)',
            'Unit Status',
            'Current Driver(s)',
            'Total Amount (FRW)',
            'Oldest Fine Days',
            'Offense Details'
        ];
    }

    public function map($row): array {
        return [
            $row['urgency'],
            $row['plateDisplay'],
            $row['status'],
            $row['driverName'],
            $row['totalAmount'],
            $row['days'],
            $row['descriptions']
        ];
    }

    public function styles(Worksheet $sheet) {
        $sheet->setAutoFilter('A1:G1');
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);

        foreach ($sheet->getRowIterator(2) as $row) {
            $rowIndex = $row->getRowIndex();
            $urgency = $sheet->getCell('A' . $rowIndex)->getValue();

            // Highlight critical debt
            if ($urgency === 'CRITICAL') {
                $sheet->getStyle("A{$rowIndex}:G{$rowIndex}")->applyFromArray([
                    'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'FFC7CE']],
                    'font' => ['color' => ['rgb' => '9C0006']]
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
        // Need to apply the same active/maintenance logic to analytics to keep numbers consistent
        $filteredFines = $this->query->where('status', 'PENDING')->get();

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

        // Header
        $sheet->setCellValue('A1', 'DEBT INSIGHTS');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);

        // Summary Table
        $sheet->setCellValue('A3', 'Metric');
        $sheet->setCellValue('B3', 'Value');
        $sheet->getStyle('A3:B3')->getFont()->setBold(true);

        $sheet->setCellValue('A4', 'Total Unpaid Amount');
        $sheet->setCellValue('B4', $validFines->sum('ticket_amount') . ' FRW');

        $sheet->setCellValue('A5', 'Critical Overdue (>14 Days)');
        $sheet->setCellValue('B5', $validFines->filter(fn($f) => \Carbon\Carbon::parse($f->issued_at)->diffInDays(now()) > 14)->count());

        // This allows the manager to select A3:B5 and click "Insert Chart" in Excel
        $sheet->getColumnDimension('A')->setWidth(30);
    }
}
