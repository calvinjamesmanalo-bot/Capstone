<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StudentEmailChanged extends Notification
{
    use Queueable;

    public function __construct(private readonly string $newEmail) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your student account email was changed')
            ->line("Your Fiat Lux student account email was changed to {$this->newEmail}.")
            ->line('If you did not make this change, contact the school administrator immediately.');
    }
}
