<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StudentIdentityApproved extends Notification
{
    use Queueable;

    public function __construct(public readonly string $continueUrl) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Student account verification approved')
            ->greeting('Your school ID verification was approved.')
            ->line('You may now continue creating your Fiat Lux student account. This private link expires after 72 hours.')
            ->action('Continue account creation', $this->continueUrl)
            ->line('If you did not submit this request, please contact the school immediately.');
    }
}
