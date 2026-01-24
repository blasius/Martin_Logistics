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

class DispatchExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithEvents
{
    protected $totalCount = 0;

    public function collection()
    {
        $vehicles = Vehicle::all();
        $this->totalCount = $vehicles->count();

        return $vehicles->map(function ($vehicle) {
            // Get Driver info with extra fields
            $driver = DB::table('users')
                ->join('drivers', 'users.id', '=', 'drivers.user_id')
                ->join('driver_vehicle_assignments', 'users.id', '=', 'driver_vehicle_assignments.driver_id')
                ->where('driver_vehicle_assignments.vehicle_id', $vehicle->id)
                ->whereNull('driver_vehicle_assignments.end_date')
                ->select('users.name', 'drivers.passport_number', 'drivers.driving_licence', 'drivers.phone', 'drivers.whatsapp_phone')
                ->first();

            // Get Trailer info with capacity_weight
            $trailer = DB::table('trailers')
                ->join('trailer_assignments', 'trailers.id', '=', 'trailer_assignments.trailer_id')
                ->where('trailer_assignments.vehicle_id', $vehicle->id)
                ->whereNull('trailer_assignments.unassigned_at')
                ->select('trailers.plate_number', 'trailers.capacity_weight')
                ->first();

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
                strtoupper($vehicle->status),
            ];
        });
    }

    public function headings(): array
    {
        // Headers now start at Row 2 because Row 1 will be our timestamp
        return [
            'PLATE NUMBER',
            'VEHICLE INFO',
            'DRIVER NAME',
            'PASSPORT',
            'LICENCE',
            'PHONE',
            'WHATSAPP',
            'TRAILER PLATE',
            'CAPACITY (KG)',
            'STATUS'
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;

                // 1. Insert a new row at the very top for the Export Date
                $sheet->insertNewRowBefore(1, 1);
                $sheet->mergeCells('A1:J1');
                $sheet->setCellValue('A1', 'FLEET DISPATCH REPORT - Generated: ' . now()->format('d-m-Y H:i'));

                // Style the new header row
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
                ]);

                // 2. Summary Footer
                $summaryRow = $this->totalCount + 2; // +1 for new header, +1 for headings, +1 for spacer
                $sheet->setCellValue("A{$summaryRow}", "TOTAL VEHICLES: " . $this->totalCount);

                $sheet->getStyle("A{$summaryRow}:J{$summaryRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFFF00']
                    ]
                ]);
            },
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Row 2 is now our Table Headings
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FFFF00']
                ]
            ],
        ];
    }
}
