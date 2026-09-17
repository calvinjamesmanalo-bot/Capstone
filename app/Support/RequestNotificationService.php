<?php

namespace App\Support;

use App\Models\RequestDocument;
use App\Models\User;
use App\Notifications\RequestStatusEmail;
use App\Notifications\RequestStatusInApp;
use Illuminate\Support\Facades\Log;
use Throwable;

class RequestNotificationService
{
    public function statusChanged(RequestDocument $request): void
    {
        $student = User::query()
            ->where('role', 'student')
            ->where('student_number', $request->student_number)
            ->first();

        if (! $student) {
            return;
        }

        try {
            $student->notify(new RequestStatusInApp($request->ticket_number, $request->status));
        } catch (Throwable $exception) {
            Log::error('Unable to save request notification.', [
                'request_id' => $request->id,
                'exception' => $exception,
            ]);
        }

        if (in_array($request->status, ['ready_to_release', 'completed', 'rejected'], true)
            && $student->email_verified_at !== null) {
            try {
                $student->notify(new RequestStatusEmail($request->ticket_number, $request->status));
            } catch (Throwable $exception) {
                Log::error('Unable to queue request status email.', [
                    'request_id' => $request->id,
                    'exception' => $exception,
                ]);
            }
        }
    }
}
