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
    protected $activeCount = 0;

    public function collection()
    {
        $vehicles = Vehicle::all();
        $this->totalCount = $vehicles->count();

        return $vehicles->map(function ($vehicle) {
            $currentDriver = DB::table('users')
                ->join('driver_vehicle_assignments', 'users.id', '=', 'driver_vehicle_assignments.driver_id')
                ->where('driver_vehicle_assignments.vehicle_id', $vehicle->id)
                ->whereNull('driver_vehicle_assignments.end_date')
                ->value('name');

            $currentTrailer = DB::table('trailers')
                ->join('trailer_assignments', 'trailers.id', '=', 'trailer_assignments.trailer_id')
                ->where('trailer_assignments.vehicle_id', $vehicle->id)
                ->whereNull('trailer_assignments.unassigned_at')
                ->value('plate_number');

            if ($currentDriver && $currentTrailer) {
                $this->activeCount++;
            }

            return [
                $vehicle->plate_number,
                $vehicle->make . ' ' . $vehicle->model,
                $currentDriver ?? '---',
                $currentTrailer ?? 'BOBTAIL',
                strtoupper($vehicle->status),
                now()->format('d-m-Y H:i')
            ];
        });
    }

    public function headings(): array
    {
        return ['PLATE NUMBER', 'VEHICLE INFO', 'ASSIGNED DRIVER', 'ATTACHED TRAILER', 'STATUS', 'EXPORTED AT'];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $lastRow = $this->totalCount + 2; // +1 for header, +1 for spacer
                $summaryRow = $lastRow + 1;

                $event->sheet->setCellValue("A{$summaryRow}", "TOTAL VEHICLES: {$this->totalCount}");
                $event->sheet->setCellValue("C{$summaryRow}", "FULLY PAIRED UNITS: {$this->activeCount}");

                // Style the summary row
                $event->sheet->getStyle("A{$summaryRow}:F{$summaryRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F1F5F9'] // Slate-100
                    ]
                ]);
            },
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
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
