<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label('Print')
                ->icon('heroicon-o-printer')
                ->action(function ($record) {
                    $order = $record->load('client');
                    $pdf = Pdf::loadView('pdf.order', compact('order'));
                    return response()->streamDownload(
                        fn () => print($pdf->output()),
                        "order-{$order->reference}.pdf"
                    );
                }),
        ];
    }
}
