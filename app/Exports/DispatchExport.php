<?php

namespace App\Exports;

use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DispatchExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    public function collection()
    {
        return Vehicle::all()->map(function ($vehicle) {
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
        return [
            'PLATE NUMBER',
            'VEHICLE INFO',
            'ASSIGNED DRIVER',
            'ATTACHED TRAILER',
            'STATUS',
            'EXPORTED AT'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold with a background color
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FFFF00'] // Slate-800
                ]
            ],
        ];
    }
}
