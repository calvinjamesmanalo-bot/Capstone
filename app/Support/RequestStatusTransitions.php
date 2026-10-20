<?php

namespace App\Support;

use App\Models\RequestDocument;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class RequestStatusTransitions
{
    private const FLOW = [
        'pending' => ['processing', 'rejected'],
        'processing' => ['processed', 'rejected'],
        'processed' => ['processing', 'ready_to_release', 'rejected'],
        'ready_to_release' => ['completed', 'processed', 'rejected'],
        'completed' => [],
        'rejected' => [],
    ];

    private const F137_TRANSFER_FLOW = [
        'pending' => ['processing', 'rejected'],
        'processing' => ['processed', 'rejected'],
        'processed' => ['processing', 'ready_for_transmission', 'rejected'],
        'ready_for_transmission' => ['transmitted', 'rejected'],
        'transmitted' => ['completed'],
        'completed' => [],
        'rejected' => [],
    ];

    private const F138_FLOW = [
        'pending' => ['processing', 'rejected'],
        'processing' => ['processed', 'ready_for_pickup', 'rejected'],
        'processed' => ['processing', 'ready_for_pickup', 'rejected'],
        'ready_for_pickup' => ['claimed', 'rejected'],
        'claimed' => [],
        'rejected' => [],
    ];

    public function allowed(RequestDocument $request, User $actor): array
    {
        $flow = $request->document_type === 'Form 137' && $request->request_purpose === 'transfer_enrollment'
            ? self::F137_TRANSFER_FLOW
            : ($request->document_type === 'Form 138' ? self::F138_FLOW : self::FLOW);
        $targets = $flow[$request->status ?? 'pending'] ?? [];
        if ($actor->role === 'registrar') {
            if ($request->is_f137_employment_exception
                && ! $request->exception_approved_at
                && $request->status === 'pending') {
                return array_values(array_filter($targets, fn (string $target): bool => in_array($target, ['processing', 'rejected'], true)));
            }
            if ($request->status !== 'processed') {
                return [];
            }

            return array_values(array_filter(
                $targets,
                fn (string $target): bool => in_array($target, ['ready_to_release', 'ready_for_transmission', 'ready_for_pickup', 'rejected'], true)
            ));
        }

        // Custom documents are prepared manually by the Records Officer, then
        // forwarded to the Registrar through the same review-only queue.
        if ($request->request_type_id !== null) {
            return match ($actor->role) {
                'admin' => $targets,
                'records_officer' => array_values(array_filter($targets, fn (string $target): bool => in_array($target, ['processing', 'processed', 'completed', 'rejected'], true))),
                default => [],
            };
        }

        return array_values(array_filter($targets, function (string $target) use ($request, $actor): bool {
            $isF137Transfer = $request->document_type === 'Form 137'
                && $request->request_purpose === 'transfer_enrollment';

            if ($isF137Transfer && $actor->role !== 'admin') {
                if ($request->status === 'processed') {
                    return $actor->role === 'registrar' && in_array($target, ['processing', 'ready_for_transmission', 'rejected'], true);
                }

                if (in_array($request->status, ['ready_for_transmission', 'transmitted'], true)) {
                    return $actor->role === 'records_officer' && in_array($target, ['transmitted', 'completed', 'rejected'], true);
                }
            }

            if ($request->review_status === 'pending' && $target === 'processing') {
                return in_array($actor->role, ['admin', 'registrar'], true);
            }

            if ($request->is_f137_employment_exception && ! $request->exception_approved_at && $target === 'processing') {
                return in_array($actor->role, ['admin', 'registrar'], true);
            }

            if (in_array($target, ['processed', 'ready_for_transmission', 'ready_for_pickup'], true)
                && ! $request->request_type_id && ! $request->hasPreparedDocument()) {
                return false;
            }

            return match ($actor->role) {
                'admin' => true,
                'registrar' => false,
                'records_officer' => in_array($target, ['processing', 'processed', 'transmitted', 'completed', 'ready_for_pickup', 'claimed', 'rejected'], true),
                default => false,
            };
        }));
    }

    public function validate(RequestDocument $request, User $actor, string $target, ?string $remarks): void
    {
        if ($target === $request->status) {
            return;
        }

        if (! in_array($target, $this->allowed($request, $actor), true)) {
            if (in_array($target, ['processed', 'ready_for_transmission', 'ready_for_pickup'], true)
                && ! $request->request_type_id && ! $request->hasPreparedDocument()) {
                throw ValidationException::withMessages([
                    'status' => 'Generate and download the protected document before forwarding it to the registrar.',
                ]);
            }

            throw ValidationException::withMessages([
                'status' => 'This status change is not allowed for your role or the current request state.',
            ]);
        }

        if ($target === 'rejected' && blank($remarks)) {
            throw ValidationException::withMessages(['remarks' => 'A reason is required to reject a request.']);
        }

    }
}
