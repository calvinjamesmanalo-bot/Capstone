<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyStudentAccountCode extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $verificationCode,
        public readonly int $expiresInMinutes = 10,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Fiat Lux student verification code')
            ->greeting('Verify your student account')
            ->line('Use this six-digit code to finish creating your student account:')
            ->line($this->verificationCode)
            ->line("This code expires in {$this->expiresInMinutes} minutes and can only be attempted five times.")
            ->line('If you did not request this account, you can safely ignore this email.');
    }
}
