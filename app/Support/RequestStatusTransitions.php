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

    public function allowed(RequestDocument $request, User $actor): array
    {
        $targets = self::FLOW[$request->status ?? 'pending'] ?? [];

        return array_values(array_filter($targets, function (string $target) use ($actor): bool {
            return match ($actor->role) {
                'admin' => true,
                'registrar' => in_array($target, ['processing', 'ready_to_release', 'completed', 'rejected'], true),
                'records_officer' => in_array($target, ['processing', 'processed', 'rejected'], true),
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
            throw ValidationException::withMessages([
                'status' => 'This status change is not allowed for your role or the current request state.',
            ]);
        }

        if ($target === 'rejected' && blank($remarks)) {
            throw ValidationException::withMessages(['remarks' => 'A reason is required to reject a request.']);
        }

        if ($target === 'ready_to_release' && (! $request->payment_confirmed || $request->clearance_status !== 'cleared')) {
            throw ValidationException::withMessages([
                'status' => 'Confirm payment and clear the balance before approving release.',
            ]);
        }
    }
}
