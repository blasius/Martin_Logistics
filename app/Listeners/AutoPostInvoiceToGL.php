<?php

namespace App\Listeners;

use App\Events\InvoiceStatusChanged;
use App\Services\AccountingService;

class AutoPostInvoiceToGL
{
    public function __construct(protected AccountingService $accountingService) {}

    public function handle(InvoiceStatusChanged $event): void
    {
        if ($event->newStatus === 'sent') {
            $this->accountingService->autoPostInvoice($event->invoice);
        }
    }
}
