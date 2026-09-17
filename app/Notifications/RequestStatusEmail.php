<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RequestStatusEmail extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $ticketNumber, public string $status)
    {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Document request update: '.$this->ticketNumber)
            ->line('Your document request '.$this->ticketNumber.' is now '.str_replace('_', ' ', $this->status).'.')
            ->action('View your requests', route('student.my-requests'));
    }
}
