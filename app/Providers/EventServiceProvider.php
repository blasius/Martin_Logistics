<?php

namespace App\Providers;

use App\Events\DeliveryConfirmed;
use App\Events\InvoiceStatusChanged;
use App\Events\OrderStatusChanged;
use App\Listeners\SendDeliveryNotification;
use App\Listeners\SendInvoiceNotification;
use App\Listeners\SendOrderNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        OrderStatusChanged::class => [
            SendOrderNotification::class,
        ],
        InvoiceStatusChanged::class => [
            SendInvoiceNotification::class,
        ],
        DeliveryConfirmed::class => [
            SendDeliveryNotification::class,
        ],
    ];

    public function boot(): void
    {
        //
    }
}
