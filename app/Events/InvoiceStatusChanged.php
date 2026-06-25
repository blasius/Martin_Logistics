<?php

namespace App\Events;

use App\Models\Invoice;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvoiceStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Invoice $invoice;
    public string $oldStatus;
    public string $newStatus;

    public function __construct(Invoice $invoice, string $oldStatus, string $newStatus)
    {
        $this->invoice = $invoice;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }
}
