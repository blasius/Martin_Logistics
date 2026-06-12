<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StationaryVehiclesExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    protected $vehicles;

    public function __construct($vehicles)
    {
        $this->vehicles = $vehicles;
    }

    public function collection()
    {
        return $this->vehicles->map(function ($v) {
            $snap = $v['snapshot'] ?? null;
            $distance = isset($v['distance_km']) ? $v['distance_km'] . ' km' : '—';

            return [
                $v['plate_number'],
                $v['driver_name'] ?? '—',
                $snap ? $snap['last_seen_at'] : '—',
                $distance,
                'Stationary',
                '',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Plate Number',
            'Driver Name',
            'Last Seen',
            'Distance (km)',
            'Status',
            'Reason',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);
    }
}
