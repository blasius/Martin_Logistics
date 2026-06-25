<?php

namespace App\Providers;

use App\Events\DeliveryConfirmed;
use App\Events\InvoiceStatusChanged;
use App\Events\OrderStatusChanged;
use App\Listeners\SendDeliveryNotification;
use App\Listeners\SendInvoiceNotification;
use App\Listeners\SendOrderNotification;
use App\Listeners\WebhookDispatch;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        OrderStatusChanged::class => [
            SendOrderNotification::class,
        ],
        InvoiceStatusChanged::class => [
            SendInvoiceNotification::class,
            \App\Listeners\AutoPostInvoiceToGL::class,
        ],
        DeliveryConfirmed::class => [
            SendDeliveryNotification::class,
        ],
    ];

    protected $subscribe = [
        WebhookDispatch::class,
    ];

    public function boot(): void
    {
        //
    }
}
