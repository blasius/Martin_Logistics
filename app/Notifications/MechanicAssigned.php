<?php

namespace App\Notifications;

use App\Models\RepairAssignment;
use App\Models\RepairRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MechanicAssigned extends Notification
{
    use Queueable;

    public function __construct(
        public RepairRequest $repairRequest,
        public RepairAssignment $assignment
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
            'assignment_id' => $this->assignment->id,
            'instructions' => $this->assignment->instructions,
            'vehicle' => $this->repairRequest->vehicle?->plate_number,
            'type' => $this->repairRequest->type,
            'priority' => $this->repairRequest->priority,
            'description' => $this->repairRequest->description,
            'message' => "You have been assigned to repair {$this->repairRequest->reference} ({$this->repairRequest->vehicle?->plate_number})",
        ];
    }
}
