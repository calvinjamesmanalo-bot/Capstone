<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class F137DeliveryAssessmentInApp extends Notification
{
    use Queueable;

    public function __construct(
        public string $ticketNumber,
        public string $method,
        public float $documentFee,
        public float $deliveryFee,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $method = $this->method === 'lbc' ? 'LBC shipping' : 'school staff delivery';
        $total = $this->documentFee + $this->deliveryFee;

        return [
            'ticket_number' => $this->ticketNumber,
            'status' => 'waiting_for_cash_payment',
            'message' => "Request {$this->ticketNumber}: {$method} selected. Amount due is PHP "
                .number_format($total, 2)
                .'. Please pay in cash to the school Records Officer and keep your official receipt.',
        ];
    }
}
