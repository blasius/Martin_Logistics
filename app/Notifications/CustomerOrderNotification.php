<?php

namespace App\Notifications;

use App\Channels\FcmChannel;
use App\Channels\SmsChannel;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomerOrderNotification extends Notification
{
    use Queueable;

    public Order $order;
    public string $status;

    public function __construct(Order $order, string $status)
    {
        $this->order = $order;
        $this->status = $status;
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];
        $prefs = $notifiable->notification_preferences ?? [];

        if ($prefs['email'] ?? true) {
            $channels[] = 'mail';
        }
        if ($prefs['sms'] ?? false) {
            $channels[] = SmsChannel::class;
        }
        if ($prefs['push'] ?? true) {
            $channels[] = FcmChannel::class;
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = match ($this->status) {
            'confirmed' => "Order {$this->order->reference} Confirmed",
            'in_transit' => "Order {$this->order->reference} In Transit",
            'delivered' => "Order {$this->order->reference} Delivered",
            'cancelled' => "Order {$this->order->reference} Cancelled",
            default => "Order {$this->order->reference} Update",
        };

        $body = match ($this->status) {
            'confirmed' => "Your order {$this->order->reference} from {$this->order->origin} to {$this->order->destination} has been confirmed.",
            'in_transit' => "Your order {$this->order->reference} is now in transit from {$this->order->origin}.",
            'delivered' => "Your order {$this->order->reference} has been delivered to {$this->order->destination}.",
            'cancelled' => "Your order {$this->order->reference} has been cancelled.",
            default => "Your order {$this->order->reference} status has been updated to {$this->status}.",
        };

        return (new MailMessage)
            ->subject($subject)
            ->greeting("Dear {$notifiable->name},")
            ->line($body)
            ->action('View Order', url("/customer/orders/{$this->order->id}"))
            ->line('Thank you for choosing our services.');
    }

    public function toFcm(object $notifiable): array
    {
        $title = match ($this->status) {
            'confirmed' => 'Order Confirmed',
            'in_transit' => 'Order In Transit',
            'delivered' => 'Order Delivered',
            'cancelled' => 'Order Cancelled',
            default => 'Order Update',
        };

        return [
            'title' => $title,
            'body' => "{$this->order->reference}: {$this->order->origin} → {$this->order->destination}",
            'data' => [
                'type' => 'order',
                'order_id' => (string) $this->order->id,
                'status' => $this->status,
            ],
        ];
    }

    public function toSms(object $notifiable): array
    {
        $body = match ($this->status) {
            'confirmed' => "Order {$this->order->reference} confirmed. {$this->order->origin} → {$this->order->destination}.",
            'in_transit' => "Order {$this->order->reference} is in transit.",
            'delivered' => "Order {$this->order->reference} delivered to {$this->order->destination}.",
            'cancelled' => "Order {$this->order->reference} has been cancelled.",
            default => "Order {$this->order->reference} updated to {$this->status}.",
        };

        return ['body' => $body];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'order_status',
            'order_id' => $this->order->id,
            'reference' => $this->order->reference,
            'origin' => $this->order->origin,
            'destination' => $this->order->destination,
            'status' => $this->status,
        ];
    }
}
