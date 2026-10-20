<?php

namespace App\Support;

use App\Models\RequestDocument;
use App\Models\User;
use App\Notifications\F137DeliveryAssessmentInApp;
use App\Notifications\RequestStatusEmail;
use App\Notifications\RequestStatusInApp;
use Illuminate\Support\Facades\Log;
use Throwable;

class RequestNotificationService
{
    public function deliveryAssessed(RequestDocument $request): void
    {
        $student = User::query()->where('role', 'student')->where('student_number', $request->student_number)->first();
        if (! $student) {
            return;
        }

        try {
            $student->notify(new F137DeliveryAssessmentInApp(
                $request->ticket_number,
                $request->f137_delivery_method,
                (float) $request->document_price,
                (float) $request->delivery_fee,
            ));
        } catch (Throwable $exception) {
            Log::error('Unable to save F137 delivery assessment notification.', ['request_id' => $request->id, 'exception' => $exception]);
        }
    }

    public function statusChanged(RequestDocument $request): void
    {
        $ticketNumber = $request->ticket_number ?: '#'.$request->id;
        $student = User::query()
            ->where('role', 'student')
            ->where('student_number', $request->student_number)
            ->first();

        if (! $student) {
            return;
        }

        try {
            $student->notify(new RequestStatusInApp($ticketNumber, $request->status));
        } catch (Throwable $exception) {
            Log::error('Unable to save request notification.', [
                'request_id' => $request->id,
                'exception' => $exception,
            ]);
        }

        if (in_array($request->status, ['ready_to_release', 'ready_for_transmission', 'transmitted', 'ready_for_pickup', 'claimed', 'completed', 'rejected'], true)
            && $student->email_verified_at !== null) {
            try {
                $student->notify(new RequestStatusEmail($ticketNumber, $request->status));
            } catch (Throwable $exception) {
                Log::error('Unable to queue request status email.', [
                    'request_id' => $request->id,
                    'exception' => $exception,
                ]);
            }
        }
    }
}
