<?php

namespace App\Listeners;

use App\Events\DeliveryConfirmed;
use App\Events\InvoiceStatusChanged;
use App\Events\OrderStatusChanged;
use App\Services\WebhookService;
use Illuminate\Events\Dispatcher;

class WebhookDispatch
{
    public function __construct(
        protected WebhookService $webhookService
    ) {}

    public function handleInvoiceStatusChanged(InvoiceStatusChanged $event): void
    {
        $this->webhookService->dispatch('invoice.status_changed', [
            'event_type' => 'invoice.status_changed',
            'invoice_id' => $event->invoice->id,
            'reference' => $event->invoice->reference,
            'old_status' => $event->oldStatus,
            'new_status' => $event->newStatus,
            'total' => $event->invoice->total,
            'client_id' => $event->invoice->client_id,
        ]);
    }

    public function handleOrderStatusChanged(OrderStatusChanged $event): void
    {
        $this->webhookService->dispatch('order.status_changed', [
            'event_type' => 'order.status_changed',
            'order_id' => $event->order->id,
            'reference' => $event->order->reference,
            'old_status' => $event->oldStatus,
            'new_status' => $event->newStatus,
            'client_id' => $event->order->client_id,
        ]);
    }

    public function handleDeliveryConfirmed(DeliveryConfirmed $event): void
    {
        $this->webhookService->dispatch('delivery.confirmed', [
            'event_type' => 'delivery.confirmed',
            'delivery_id' => $event->proofOfDelivery->id,
            'status' => $event->proofOfDelivery->status,
            'order_id' => $event->proofOfDelivery->order_id,
            'trip_id' => $event->proofOfDelivery->trip_id,
            'received_by' => $event->proofOfDelivery->received_by_name,
        ]);
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            InvoiceStatusChanged::class,
            [$this, 'handleInvoiceStatusChanged']
        );
        $events->listen(
            OrderStatusChanged::class,
            [$this, 'handleOrderStatusChanged']
        );
        $events->listen(
            DeliveryConfirmed::class,
            [$this, 'handleDeliveryConfirmed']
        );
    }
}
