<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class TurnstileVerifier
{
    public function verify(?string $token, ?string $ipAddress): bool
    {
        if (! config('services.turnstile.enabled', true)) {
            return ! app()->environment('production');
        }

        $secret = config('services.turnstile.secret_key');

        if (! is_string($secret) || $secret === '' || ! is_string($token) || $token === '' || strlen($token) > 2048) {
            return false;
        }

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->timeout(5)
                ->retry(2, 100, throw: false)
                ->post((string) config('services.turnstile.verify_url'), [
                    'secret' => $secret,
                    'response' => $token,
                    'remoteip' => $ipAddress,
                    'idempotency_key' => (string) Str::uuid(),
                ]);
        } catch (Throwable $exception) {
            // Never include the response token or secret in logs.
            Log::channel('security')->warning('Turnstile verification service could not be reached.', [
                'exception' => $exception::class,
            ]);

            return false;
        }

        if (! $response->successful()) {
            Log::channel('security')->warning('Turnstile verification returned an HTTP error.', [
                'status' => $response->status(),
            ]);

            return false;
        }

        if ($response->json('success') !== true) {
            $errorCodes = $response->json('error-codes', []);
            Log::channel('security')->notice('Turnstile rejected a security-check token.', [
                'error_codes' => is_array($errorCodes)
                    ? array_values(array_filter($errorCodes, 'is_string'))
                    : [],
            ]);

            return false;
        }

        $expectedAction = (string) config('services.turnstile.expected_action', 'login');
        $actualAction = $response->json('action');
        $testResponseWithoutAction = config('services.turnstile.using_test_keys', false)
            && $actualAction === null;

        if (! $testResponseWithoutAction
            && (! is_string($actualAction) || ! hash_equals($expectedAction, $actualAction))) {
            Log::channel('security')->notice('Turnstile action did not match the login action.', [
                'expected_action' => $expectedAction,
                'actual_action' => is_string($actualAction) ? $actualAction : null,
            ]);

            return false;
        }

        $allowedHostnames = config('services.turnstile.allowed_hostnames', []);
        $hostname = $response->json('hostname');
        if ($allowedHostnames !== [] && (! is_string($hostname) || ! in_array($hostname, $allowedHostnames, true))) {
            Log::channel('security')->notice('Turnstile hostname was not allowed.', [
                'hostname' => is_string($hostname) ? $hostname : null,
            ]);

            return false;
        }

        return true;
    }
}
