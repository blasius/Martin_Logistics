<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class ProfilePasswordReset extends Notification
{
    use Queueable;

    public string $token;

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $url = config('app.url') . '/portal/password-reset?token=' . $this->token . '&email=' . urlencode($notifiable->getEmailForPasswordReset());

        return (new MailMessage)
            ->subject('Reset Your Martin Logistics Password')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('You requested a password change for your Martin Logistics account.')
            ->line('Click the button below to set a new password. This link will expire in 60 minutes.')
            ->action('Reset Password', $url)
            ->line('If you did not request a password change, no further action is required.');
    }
}
