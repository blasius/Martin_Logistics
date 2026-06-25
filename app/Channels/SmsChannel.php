<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Twilio\Rest\Client;

class SmsChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        $phone = $notifiable->contacts()
            ->where('type', 'phone')
            ->whereNotNull('verified_at')
            ->value('value');

        if (!$phone) {
            return;
        }

        $payload = $notification->toSms($notifiable);

        try {
            $twilio = new Client(
                config('services.twilio.sid'),
                config('services.twilio.token')
            );

            $twilio->messages->create($phone, [
                'from' => config('services.twilio.from'),
                'body' => $payload['body'] ?? '',
            ]);
        } catch (\Throwable $e) {
            logger()->error("SMS send failed to {$phone}: {$e->getMessage()}");
        }
    }
}
