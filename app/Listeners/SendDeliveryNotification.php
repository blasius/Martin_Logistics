<?php

namespace App\Listeners;

use App\Events\DeliveryConfirmed;
use App\Notifications\CustomerDeliveryNotification;

class SendDeliveryNotification
{
    public function handle(DeliveryConfirmed $event): void
    {
        $pod = $event->proofOfDelivery;
        $order = $pod->order;
        if (!$order || !$order->client?->user) {
            return;
        }

        $order->client->user->notify(new CustomerDeliveryNotification($pod));
    }
}
