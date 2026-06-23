<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;

class FcmChannel
{
    public function __construct(
        protected Messaging $messaging
    ) {}

    public function send(object $notifiable, Notification $notification): void
    {
        $token = $notifiable->fcm_token;

        if (!$token) {
            return;
        }

        $payload = $notification->toFcm($notifiable);

        $message = CloudMessage::new()
            ->toToken($token)
            ->withNotification(FcmNotification::create(
                $payload['title'] ?? '',
                $payload['body'] ?? '',
            ))
            ->withData($payload['data'] ?? []);

        try {
            $this->messaging->send($message);
        } catch (\Throwable $e) {
            logger()->error("FCM send failed for user {$notifiable->id}: {$e->getMessage()}");
        }
    }
}
