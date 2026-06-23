<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;

class FcmChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        $token = $notifiable->fcm_token;

        if (!$token) {
            return;
        }

        try {
            $messaging = app(\Kreait\Firebase\Contract\Messaging::class);
        } catch (\Throwable $e) {
            logger()->warning('FCM not configured, skipping push: '.$e->getMessage());
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
            $messaging->send($message);
        } catch (\Throwable $e) {
            logger()->error("FCM send failed for user {$notifiable->id}: {$e->getMessage()}");
        }
    }
}
