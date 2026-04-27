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
        // First Goal: Remove paid fines. Focus only on PENDING.
        $this->query = $query->where('status', 'PENDING');
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

    public function __construct($query) { $this->query = $query; }

    public function title(): string { return 'Active Debt (Unpaid)'; }

    public function collection() { return $this->query->get(); }

    public function headings(): array {
        return [
            'Urgency',
            'Plate Number',
            'Unit Type',
            'Current Driver',
            'Linked Trailer',
            'Amount (FRW)',
            'Days Unpaid',
            'Offense Details'
        ];
    }

    public function map($fine): array {
        $days = \Carbon\Carbon::parse($fine->issued_at)->diffInDays(now());

        // Second Goal: Build Associations (Logic inspired by DispatchController)
        $driverName = 'No Driver Assigned';
        $linkedTrailer = 'N/A';

        if ($fine->fineable_type === Vehicle::class) {
            // Find who is currently driving this truck
            $driverName = DB::table('users')
                ->join('driver_vehicle_assignments', 'users.id', '=', 'driver_vehicle_assignments.driver_id')
                ->where('vehicle_id', $fine->fineable_id)
                ->whereNull('end_date')
                ->value('name') ?? 'Standby';

            // Find what is currently attached to this truck
            $linkedTrailer = DB::table('trailers')
                ->join('trailer_assignments', 'trailers.id', '=', 'trailer_assignments.trailer_id')
                ->where('vehicle_id', $fine->fineable_id)
                ->whereNull('unassigned_at')
                ->value('plate_number') ?? 'None';
        }

        return [
            $days > 14 ? 'CRITICAL' : 'PENDING',
            $fine->plate_number,
            str_replace('App\\Models\\', '', $fine->fineable_type),
            $driverName,
            $linkedTrailer,
            $fine->ticket_amount,
            $days,
            $fine->violations->pluck('violation_name')->implode(', ')
        ];
    }

    public function styles(Worksheet $sheet) {
        $sheet->setAutoFilter('A1:H1'); // Third Goal: Offer Filters
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);

        foreach ($sheet->getRowIterator(2) as $row) {
            $rowIndex = $row->getRowIndex();
            $urgency = $sheet->getCell('A' . $rowIndex)->getValue();

            if ($urgency === 'CRITICAL') {
                $sheet->getStyle("A{$rowIndex}:H{$rowIndex}")->applyFromArray([
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
        $fines = $this->query->get();

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
