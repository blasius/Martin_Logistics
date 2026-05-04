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
        $this->query = $query;
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
        // Get all active/maintenance vehicles
        $vehicles = Vehicle::whereIn('status', ['active', 'maintenance'])->get();
        $exportData = collect();

        foreach ($vehicles as $vehicle) {
            // Get current drivers
            $drivers = DB::table('users')
                ->join('driver_vehicle_assignments', 'users.id', '=', 'driver_vehicle_assignments.driver_id')
                ->where('vehicle_id', $vehicle->id)
                ->whereNull('end_date')
                ->pluck('name')
                ->implode(', ');
            $driverName = $drivers ?: 'No Driver';

            // Get current trailer
            $linkedTrailer = DB::table('trailers')
                ->join('trailer_assignments', 'trailers.id', '=', 'trailer_assignments.trailer_id')
                ->where('vehicle_id', $vehicle->id)
                ->whereNull('unassigned_at')
                ->first();

            $trailerPlate = $linkedTrailer ? $linkedTrailer->plate_number : 'None';
            $plateDisplay = $trailerPlate !== 'None' ? "{$vehicle->plate_number}/{$trailerPlate}" : $vehicle->plate_number;

            // Get pending fines for the vehicle
            $vehicleFines = TrafficFine::with('violations')
                ->where('status', 'PENDING')
                ->where('fineable_type', Vehicle::class)
                ->where('fineable_id', $vehicle->id)
                ->get();

            // Get pending fines for the trailer
            $trailerFines = collect();
            if ($linkedTrailer) {
                $trailerFines = TrafficFine::with('violations')
                    ->where('status', 'PENDING')
                    ->where('fineable_type', Trailer::class)
                    ->where('fineable_id', $linkedTrailer->trailer_id)
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
    public function __construct($query) { $this->query = $query; }
    public function title(): string { return 'Management Charts'; }

    public function styles(Worksheet $sheet) {
        $fines = $this->query->where('status', 'PENDING')->get();

        // Header
        $sheet->setCellValue('A1', 'DEBT INSIGHTS');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);

        // Summary Table
        $sheet->setCellValue('A3', 'Metric');
        $sheet->setCellValue('B3', 'Value');
        $sheet->getStyle('A3:B3')->getFont()->setBold(true);

        $sheet->setCellValue('A4', 'Total Unpaid Amount');
        $sheet->setCellValue('B4', $fines->sum('ticket_amount') . ' FRW');

        $sheet->setCellValue('A5', 'Critical Overdue (>14 Days)');
        $sheet->setCellValue('B5', $fines->filter(fn($f) => \Carbon\Carbon::parse($f->issued_at)->diffInDays(now()) > 14)->count());

        // This allows the manager to select A3:B5 and click "Insert Chart" in Excel
        $sheet->getColumnDimension('A')->setWidth(30);
    }
}
