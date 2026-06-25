<?php

namespace App\Listeners;

use App\Events\InvoiceStatusChanged;
use App\Notifications\CustomerInvoiceNotification;

class SendInvoiceNotification
{
    public function handle(InvoiceStatusChanged $event): void
    {
        $user = $event->invoice->client?->user;
        if (!$user) {
            return;
        }

        $user->notify(new CustomerInvoiceNotification(
            $event->invoice,
            $event->newStatus
        ));
    }
}
