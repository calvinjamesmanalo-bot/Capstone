<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyStudentEmailChange extends Notification
{
    use Queueable;

    public function __construct(public readonly string $verificationUrl) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Verify your new student account email')
            ->line('A request was made to use this email address for a Fiat Lux student account.')
            ->action('Verify new email address', $this->verificationUrl)
            ->line('This link expires in 60 minutes. If you did not request this change, you can ignore this message.');
    }
}
