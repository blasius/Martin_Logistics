<?php

namespace App\Notifications;

use App\Channels\FcmChannel;
use App\Models\ProofOfDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomerDeliveryNotification extends Notification
{
    use Queueable;

    public ProofOfDelivery $proofOfDelivery;

    public function __construct(ProofOfDelivery $proofOfDelivery)
    {
        $this->proofOfDelivery = $proofOfDelivery;
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];
        $prefs = $notifiable->notification_preferences ?? [];

        if ($prefs['email'] ?? true) {
            $channels[] = 'mail';
        }
        if ($prefs['push'] ?? true) {
            $channels[] = FcmChannel::class;
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->proofOfDelivery->order;

        return (new MailMessage)
            ->subject("Delivery Confirmed - {$order->reference}")
            ->greeting("Dear {$notifiable->name},")
            ->line("Your order {$order->reference} has been delivered to {$order->destination}.")
            ->line("Received by: {$this->proofOfDelivery->received_by_name}")
            ->action('View Delivery Details', url("/customer/orders/{$order->id}"))
            ->line('Thank you for choosing our services.');
    }

    public function toFcm(object $notifiable): array
    {
        $order = $this->proofOfDelivery->order;

        return [
            'title' => 'Delivery Confirmed',
            'body' => "{$order->reference} delivered to {$order->destination}. Signed by {$this->proofOfDelivery->received_by_name}",
            'data' => [
                'type' => 'delivery',
                'order_id' => (string) $order->id,
                'pod_id' => (string) $this->proofOfDelivery->id,
            ],
        ];
    }

    public function toArray(object $notifiable): array
    {
        $order = $this->proofOfDelivery->order;

        return [
            'type' => 'delivery_confirmed',
            'order_id' => $order->id,
            'reference' => $order->reference,
            'received_by' => $this->proofOfDelivery->received_by_name,
            'delivered_at' => $this->proofOfDelivery->delivered_at,
        ];
    }
}
