<?php

namespace App\Notifications;

use App\Models\RepairRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RepairRequestCreated extends Notification
{
    use Queueable;

    public function __construct(
        public RepairRequest $repairRequest
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'repair_request_id' => $this->repairRequest->id,
            'reference' => $this->repairRequest->reference,
            'vehicle' => $this->repairRequest->vehicle?->plate_number,
            'type' => $this->repairRequest->type,
            'priority' => $this->repairRequest->priority,
            'description' => $this->repairRequest->description,
            'message' => "A repair request ({$this->repairRequest->reference}) has been created for {$this->repairRequest->vehicle?->plate_number}. Please review in your app.",
        ];
    }
}
