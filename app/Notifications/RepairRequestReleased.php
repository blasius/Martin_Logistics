<?php

namespace App\Notifications;

use App\Models\RepairRelease;
use App\Models\RepairRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RepairRequestReleased extends Notification
{
    use Queueable;

    public function __construct(
        public RepairRequest $repairRequest,
        public RepairRelease $release
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', \App\Channels\FcmChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'repair_request_id' => $this->repairRequest->id,
            'reference' => $this->repairRequest->reference,
            'vehicle' => $this->repairRequest->vehicle?->plate_number,
            'type' => $this->repairRequest->type,
            'released_at' => $this->release->released_at,
            'unresolved_issues' => $this->release->unresolved_issues,
            'message' => "Your vehicle {$this->repairRequest->vehicle?->plate_number} has been released from the workshop.",
        ];
    }

    public function toFcm(object $notifiable): array
    {
        return [
            'title' => 'Vehicle Released',
            'body' => "{$this->repairRequest->vehicle?->plate_number} has been released from the workshop ({$this->repairRequest->reference}).",
            'data' => [
                'type' => 'repair_released',
                'repair_request_id' => (string) $this->repairRequest->id,
                'reference' => $this->repairRequest->reference,
            ],
        ];
    }
}
