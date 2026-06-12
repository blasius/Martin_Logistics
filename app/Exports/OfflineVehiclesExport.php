<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OfflineVehiclesExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
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
            return [
                $v['plate_number'],
                $v['driver_name'] ?? '—',
                $snap ? $snap['last_seen_at'] : 'No signal',
                'Offline',
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
            'Status',
            'Reason',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:E1')->getFont()->setBold(true);
    }
}
