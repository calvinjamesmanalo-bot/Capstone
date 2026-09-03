<?php

return [

    'student_registration' => [
        // Temporarily false while the school has no official student roster.
        // Set true after importing student numbers, names, and official emails.
        'require_roster' => (bool) env('STUDENT_REGISTRATION_REQUIRE_ROSTER', false),
    ],

    'authentication' => [
        // Redis is recommended in production. Leave this null to use CACHE_STORE.
        'cache_store' => env('AUTH_SECURITY_CACHE_STORE'),
        'max_attempts' => (int) env('AUTH_MAX_ATTEMPTS', 5),
        'attempt_window_seconds' => (int) env('AUTH_ATTEMPT_WINDOW_SECONDS', 900),
        'lockout_minutes' => array_map(
            'intval',
            explode(',', (string) env('AUTH_LOCKOUT_MINUTES', '15,30,60'))
        ),
        'anomaly_window_minutes' => (int) env('AUTH_ANOMALY_WINDOW_MINUTES', 15),
        'distinct_ip_threshold' => (int) env('AUTH_DISTINCT_IP_THRESHOLD', 3),
        'distinct_account_threshold' => (int) env('AUTH_DISTINCT_ACCOUNT_THRESHOLD', 10),
        'alert_channel' => env('AUTH_ALERT_LOG_CHANNEL', 'security'),
    ],

];
