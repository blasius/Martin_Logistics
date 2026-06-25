<?php

namespace App\Notifications;

use App\Channels\FcmChannel;
use App\Channels\SmsChannel;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomerInvoiceNotification extends Notification
{
    use Queueable;

    public Invoice $invoice;
    public string $status;

    public function __construct(Invoice $invoice, string $status)
    {
        $this->invoice = $invoice;
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
            'sent' => "Invoice {$this->invoice->reference} Ready",
            'paid' => "Invoice {$this->invoice->reference} Paid",
            'overdue' => "Invoice {$this->invoice->reference} Overdue",
            'cancelled' => "Invoice {$this->invoice->reference} Cancelled",
            default => "Invoice {$this->invoice->reference} Update",
        };

        $body = match ($this->status) {
            'sent' => "Invoice {$this->invoice->reference} for {$this->invoice->total} is now available.",
            'paid' => "Payment of {$this->invoice->total} received for invoice {$this->invoice->reference}. Thank you.",
            'overdue' => "Invoice {$this->invoice->reference} of {$this->invoice->total} is now overdue. Please remit payment.",
            'cancelled' => "Invoice {$this->invoice->reference} has been cancelled.",
            default => "Invoice {$this->invoice->reference} status updated to {$this->status}.",
        };

        return (new MailMessage)
            ->subject($subject)
            ->greeting("Dear {$notifiable->name},")
            ->line($body)
            ->action('View Invoice', url("/portal/invoices/{$this->invoice->id}"))
            ->line('Thank you for your business.');
    }

    public function toFcm(object $notifiable): array
    {
        $title = match ($this->status) {
            'sent' => 'Invoice Ready',
            'paid' => 'Payment Received',
            'overdue' => 'Invoice Overdue',
            'cancelled' => 'Invoice Cancelled',
            default => 'Invoice Update',
        };

        return [
            'title' => $title,
            'body' => "{$this->invoice->reference}: {$this->invoice->total}",
            'data' => [
                'type' => 'invoice',
                'invoice_id' => (string) $this->invoice->id,
                'status' => $this->status,
            ],
        ];
    }

    public function toSms(object $notifiable): array
    {
        $body = match ($this->status) {
            'sent' => "Invoice {$this->invoice->reference} for {$this->invoice->total} is ready.",
            'paid' => "Payment received for invoice {$this->invoice->reference}.",
            'overdue' => "Invoice {$this->invoice->reference} of {$this->invoice->total} is overdue.",
            'cancelled' => "Invoice {$this->invoice->reference} cancelled.",
            default => "Invoice {$this->invoice->reference} updated.",
        };

        return ['body' => $body];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'invoice_status',
            'invoice_id' => $this->invoice->id,
            'reference' => $this->invoice->reference,
            'total' => $this->invoice->total,
            'status' => $this->status,
        ];
    }
}
