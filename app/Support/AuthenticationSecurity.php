<?php

namespace App\Support;

use App\Models\LoginAttemptLog;
use App\Models\User;
use Illuminate\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class AuthenticationSecurity
{
    public function secondsUntilAvailable(string $identifier, ?string $ipAddress): int
    {
        return max(
            $this->remainingSeconds($this->accountLockKey($identifier)),
            $this->remainingSeconds($this->ipLockKey($ipAddress))
        );
    }

    public function recordFailure(
        string $identifier,
        ?string $ipAddress,
        ?string $userAgent,
        ?string $accountType,
        string $reason
    ): int {
        $identifier = Str::lower(trim($identifier));
        $ipAddress = $ipAddress ?: 'unknown';
        $userAgent = $this->sanitizeUserAgent($userAgent);

        $log = $this->writeAttemptLog($identifier, $ipAddress, $userAgent, $accountType, $reason);
        $this->detectAnomaly($log, $identifier, $ipAddress);

        $attemptTtl = max(60, (int) config('security.authentication.attempt_window_seconds', 900));
        $accountAttempts = $this->increment($this->accountAttemptsKey($identifier), $attemptTtl);
        $ipAttempts = $this->increment($this->ipAttemptsKey($ipAddress), $attemptTtl);
        $maxAttempts = max(1, (int) config('security.authentication.max_attempts', 5));

        if ($accountAttempts >= $maxAttempts) {
            $this->startAccountLock($identifier);
        }

        if ($ipAttempts >= $maxAttempts) {
            $this->startIpLock($ipAddress);
        }

        return $this->secondsUntilAvailable($identifier, $ipAddress);
    }

    public function recordBlockedAttempt(
        string $identifier,
        ?string $ipAddress,
        ?string $userAgent,
        ?string $accountType
    ): void {
        $identifier = Str::lower(trim($identifier));
        $ipAddress = $ipAddress ?: 'unknown';
        $log = $this->writeAttemptLog(
            $identifier,
            $ipAddress,
            $this->sanitizeUserAgent($userAgent),
            $accountType,
            'temporarily_locked'
        );
        $this->detectAnomaly($log, $identifier, $ipAddress);
    }

    public function clearAccount(string $identifier): void
    {
        $this->cache()->forget($this->accountAttemptsKey($identifier));
        $this->cache()->forget($this->accountLockKey($identifier));
        $this->cache()->forget($this->accountLockLevelKey($identifier));
    }

    private function startAccountLock(string $identifier): void
    {
        $levelKey = $this->accountLockLevelKey($identifier);
        $this->cache()->add($levelKey, 0, now()->addDay());
        $level = max(1, (int) $this->cache()->increment($levelKey));
        $this->cache()->put($levelKey, $level, now()->addDay());

        $durations = array_values(array_filter(
            config('security.authentication.lockout_minutes', [15, 30, 60]),
            fn ($minutes) => (int) $minutes > 0
        ));
        $durations = $durations === [] ? [15] : $durations;
        $minutes = (int) $durations[min($level - 1, count($durations) - 1)];

        $this->putLock($this->accountLockKey($identifier), $minutes);
        $this->cache()->forget($this->accountAttemptsKey($identifier));
    }

    private function startIpLock(string $ipAddress): void
    {
        $durations = config('security.authentication.lockout_minutes', [15]);
        $minutes = max(1, (int) ($durations[0] ?? 15));

        $this->putLock($this->ipLockKey($ipAddress), $minutes);
        $this->cache()->forget($this->ipAttemptsKey($ipAddress));
    }

    private function putLock(string $key, int $minutes): void
    {
        $expiresAt = now()->addMinutes($minutes);
        $this->cache()->put($key, $expiresAt->timestamp, $expiresAt);
    }

    private function remainingSeconds(string $key): int
    {
        $expiresAt = (int) $this->cache()->get($key, 0);

        return max(0, $expiresAt - now()->timestamp);
    }

    private function increment(string $key, int $ttlSeconds): int
    {
        if ($this->cache()->add($key, 1, now()->addSeconds($ttlSeconds))) {
            return 1;
        }

        return (int) $this->cache()->increment($key);
    }

    private function writeAttemptLog(
        string $identifier,
        string $ipAddress,
        ?string $userAgent,
        ?string $accountType,
        string $reason
    ): ?LoginAttemptLog {
        $context = [
            'attempted_identifier' => $identifier,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'account_type' => $accountType,
            'failure_reason' => $reason,
            'occurred_at' => now()->toIso8601String(),
        ];

        Log::channel('security')->notice('Authentication attempt failed.', $context);

        try {
            return LoginAttemptLog::create([
                'user_id' => User::query()->whereRaw('LOWER(email) = ?', [$identifier])->value('id'),
                ...$context,
            ]);
        } catch (Throwable $exception) {
            Log::channel('security')->error('Authentication audit database write failed.', [
                'exception' => $exception::class,
            ]);

            return null;
        }
    }

    private function detectAnomaly(?LoginAttemptLog $log, string $identifier, string $ipAddress): void
    {
        if ($log === null) {
            return;
        }

        $since = now()->subMinutes(max(1, (int) config('security.authentication.anomaly_window_minutes', 15)));
        $ipCount = LoginAttemptLog::query()
            ->where('attempted_identifier', $identifier)
            ->where('created_at', '>=', $since)
            ->distinct()->count('ip_address');
        $accountCount = LoginAttemptLog::query()
            ->where('ip_address', $ipAddress)
            ->where('created_at', '>=', $since)
            ->distinct()->count('attempted_identifier');

        $isAnomalous = $ipCount >= max(2, (int) config('security.authentication.distinct_ip_threshold', 3))
            || $accountCount >= max(2, (int) config('security.authentication.distinct_account_threshold', 10));

        if (! $isAnomalous) {
            return;
        }

        $log->update(['anomaly_detected' => true]);
        $alertKey = 'auth-alert:'.$this->fingerprint($identifier.'|'.$ipAddress);
        if ($this->cache()->add(
            $alertKey,
            true,
            now()->addMinutes(max(1, (int) config('security.authentication.anomaly_window_minutes', 15)))
        )) {
            Log::channel((string) config('security.authentication.alert_channel', 'security'))
                ->warning('Unusual login activity detected.', [
                    'account_fingerprint' => $this->fingerprint($identifier),
                    'ip_address' => $ipAddress,
                    'distinct_ip_count' => $ipCount,
                    'distinct_account_count' => $accountCount,
                    'occurred_at' => now()->toIso8601String(),
                ]);
        }
    }

    private function cache(): Repository
    {
        $store = config('security.authentication.cache_store');

        return Cache::store(is_string($store) && $store !== '' ? $store : null);
    }

    private function accountAttemptsKey(string $identifier): string
    {
        return 'auth:attempts:account:'.$this->fingerprint(Str::lower(trim($identifier)));
    }

    private function ipAttemptsKey(?string $ipAddress): string
    {
        return 'auth:attempts:ip:'.$this->fingerprint($ipAddress ?: 'unknown');
    }

    private function accountLockKey(string $identifier): string
    {
        return 'auth:lock:account:'.$this->fingerprint(Str::lower(trim($identifier)));
    }

    private function ipLockKey(?string $ipAddress): string
    {
        return 'auth:lock:ip:'.$this->fingerprint($ipAddress ?: 'unknown');
    }

    private function accountLockLevelKey(string $identifier): string
    {
        return 'auth:lock-level:account:'.$this->fingerprint(Str::lower(trim($identifier)));
    }

    private function fingerprint(string $value): string
    {
        return hash_hmac('sha256', $value, (string) config('app.key', 'application-key'));
    }

    private function sanitizeUserAgent(?string $userAgent): ?string
    {
        if (! is_string($userAgent) || $userAgent === '') {
            return null;
        }

        $sanitized = preg_replace('/[\x00-\x1F\x7F]/', '', $userAgent);

        return Str::limit((string) $sanitized, 1000, '');
    }
}
