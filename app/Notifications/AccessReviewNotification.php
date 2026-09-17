<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AccessReviewNotification extends Notification
{
    use Queueable;

    public function __construct(
        public array $summary
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'flagged' => $this->summary['flagged'] ?? 0,
            'locked' => $this->summary['locked'] ?? 0,
            'inactive_users' => $this->summary['inactive_users'] ?? 0,
            'idle_dispatchers' => $this->summary['idle_dispatchers'] ?? 0,
            'drivers_without_vehicle' => $this->summary['drivers_without_vehicle'] ?? 0,
            'mechanics_inactive' => $this->summary['mechanics_inactive'] ?? 0,
            'orphan_roles' => $this->summary['orphan_roles'] ?? 0,
            'message' => "Access review: {$this->summary['flagged']} account(s) flagged.",
        ];
    }
}