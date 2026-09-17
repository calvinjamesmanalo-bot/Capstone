<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RequestStatusInApp extends Notification
{
    use Queueable;

    public function __construct(public string $ticketNumber, public string $status) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'ticket_number' => $this->ticketNumber,
            'status' => $this->status,
            'message' => 'Request '.$this->ticketNumber.' is now '.str_replace('_', ' ', $this->status).'.',
        ];
    }
}
