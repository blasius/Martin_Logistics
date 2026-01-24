<?php

namespace App\Exports;

use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;

class DispatchExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithEvents
{
    protected $totalCount = 0;
    protected $dataRows = [];

    public function collection()
    {
        $vehicles = Vehicle::all();
        $this->totalCount = $vehicles->count();

        return $vehicles->map(function ($vehicle) {
            $driver = DB::table('users')
                ->join('drivers', 'users.id', '=', 'drivers.user_id')
                ->join('driver_vehicle_assignments', 'users.id', '=', 'driver_vehicle_assignments.driver_id')
                ->where('driver_vehicle_assignments.vehicle_id', $vehicle->id)
                ->whereNull('driver_vehicle_assignments.end_date')
                ->select('users.name', 'drivers.passport_number', 'drivers.driving_licence', 'drivers.phone', 'drivers.whatsapp_phone')
                ->first();

            $trailer = DB::table('trailers')
                ->join('trailer_assignments', 'trailers.id', '=', 'trailer_assignments.trailer_id')
                ->where('trailer_assignments.vehicle_id', $vehicle->id)
                ->whereNull('trailer_assignments.unassigned_at')
                ->select('trailers.plate_number', 'trailers.capacity_weight')
                ->first();

            $status = strtoupper($vehicle->status);
            $this->dataRows[] = $status; // Track statuses for styling later

            return [
                $vehicle->plate_number,
                $vehicle->make . ' ' . $vehicle->model,
                $driver->name ?? '---',
                $driver->passport_number ?? '---',
                $driver->driving_licence ?? '---',
                $driver->phone ?? '---',
                $driver->whatsapp_phone ?? '---',
                $trailer->plate_number ?? '---',
                $trailer->capacity_weight ?? '---',
                $status,
            ];
        });
    }

    public function headings(): array
    {
        return ['PLATE NUMBER', 'VEHICLE INFO', 'DRIVER NAME', 'PASSPORT', 'LICENCE', 'PHONE', 'WHATSAPP', 'TRAILER PLATE', 'CAPACITY (TN)', 'STATUS'];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;
                $sheet->insertNewRowBefore(1, 1);
                $sheet->mergeCells('A1:J1');
                $sheet->setCellValue('A1', 'FLEET DISPATCH STATUS - Generated on: ' . now()->format('d-m-Y H:i'));

                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
                ]);

                // Apply Conditional Styling to the Status Column (Column J)
                foreach ($this->dataRows as $index => $status) {
                    $rowNumber = $index + 3; // +1 for header row, +1 for headings row, +1 for 0-index offset
                    $cellCoordinate = "J{$rowNumber}";

                    if ($status === 'ACTIVE') {
                        $sheet->getStyle($cellCoordinate)->applyFromArray([
                            'font' => ['color' => ['rgb' => '064E3B'], 'bold' => true], // Dark Green Text
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D1FAE5']] // Light Green BG
                        ]);
                    } elseif (in_array($status, ['INACTIVE', 'MAINTENANCE', 'OUT OF SERVICE'])) {
                        $sheet->getStyle($cellCoordinate)->applyFromArray([
                            'font' => ['color' => ['rgb' => '991B1B'], 'bold' => true], // Dark Red Text
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEE2E2']] // Light Red BG
                        ]);
                    }
                }

                // Summary Footer
                $summaryRow = $this->totalCount + 3;
                $sheet->setCellValue("A{$summaryRow}", "TOTAL VEHICLES: " . $this->totalCount);
                $sheet->getStyle("A{$summaryRow}:J{$summaryRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']]
                ]);
            },
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFF00']]
            ],
        ];
    }
}
