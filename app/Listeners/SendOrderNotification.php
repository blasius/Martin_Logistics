<?php

namespace App\Listeners;

use App\Events\OrderStatusChanged;
use App\Notifications\CustomerOrderNotification;

class SendOrderNotification
{
    public function handle(OrderStatusChanged $event): void
    {
        $user = $event->order->client?->user;
        if (!$user) {
            return;
        }

        $channels = match ($event->newStatus) {
            'confirmed' => ['database', 'mail'],
            'in_transit' => ['database', 'mail'],
            'delivered' => ['database'],
            'cancelled' => ['database', 'mail'],
            default => ['database'],
        };

        $user->notify(new CustomerOrderNotification(
            $event->order,
            $event->newStatus
        ));
    }
}
