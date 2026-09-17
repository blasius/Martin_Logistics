<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SupportTicketAssigned extends Notification
{
    use Queueable;

    public function __construct(
        public SupportTicket $ticket
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', \App\Channels\FcmChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'reference' => $this->ticket->reference,
            'source' => $this->ticket->source,
            'priority' => $this->ticket->priority,
            'subject_type' => $this->ticket->subject_type,
            'subject_id' => $this->ticket->subject_id,
            'vehicle' => $this->ticket->subject_type === \App\Models\Vehicle::class
                ? $this->ticket->subject?->plate_number
                : ($this->ticket->subject?->vehicle_plate_snapshot ?? null),
            'message' => "Auto-ticket {$this->ticket->reference} assigned to you.",
        ];
    }

    public function toFcm(object $notifiable): array
    {
        $vehicle = $this->ticket->subject_type === \App\Models\Vehicle::class
            ? $this->ticket->subject?->plate_number
            : ($this->ticket->subject?->vehicle_plate_snapshot ?? null);

        return [
            'title' => 'Support ticket assigned',
            'body' => "{$this->ticket->reference}: {$this->ticket->title}" . ($vehicle ? " ({$vehicle})" : ''),
            'data' => [
                'type' => 'support_ticket_assigned',
                'ticket_id' => (string) $this->ticket->id,
                'reference' => $this->ticket->reference,
            ],
        ];
    }
}