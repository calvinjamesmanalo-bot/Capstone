<?php

return [

    'headers' => [
        'enabled' => (bool) env('SECURITY_HEADERS_ENABLED', true),
        // Inline scripts/styles remain temporarily allowed because the current
        // Blade templates and Tailwind CDN runtime use them. External origins
        // are limited to the services intentionally used by this application.
        'content_security_policy' => implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "object-src 'none'",
            "frame-ancestors 'none'",
            "form-action 'self'",
            "script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdn.jsdelivr.net https://challenges.cloudflare.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://fonts.bunny.net",
            "font-src 'self' data: https://fonts.gstatic.com https://fonts.bunny.net",
            "img-src 'self' data: blob:",
            "connect-src 'self' https://challenges.cloudflare.com",
            'frame-src https://challenges.cloudflare.com',
            "worker-src 'self' blob:",
            "media-src 'self' data: blob:",
            "manifest-src 'self'",
        ]),
    ],

    'https' => [
        // Local HTTP remains available. Production enables HTTPS enforcement
        // unless FORCE_HTTPS is explicitly overridden by the deployment.
        'force' => (bool) env('FORCE_HTTPS', env('APP_ENV', 'production') === 'production'),
        'hsts' => [
            'enabled' => (bool) env('HTTPS_HSTS_ENABLED', true),
            'max_age' => (int) env('HTTPS_HSTS_MAX_AGE', 31536000),
            // Enable only when every subdomain is served exclusively by HTTPS.
            'include_subdomains' => (bool) env('HTTPS_HSTS_INCLUDE_SUBDOMAINS', false),
        ],
    ],

    'student_registration' => [
        // Temporarily false while the school has no official student roster.
        // Set true after importing student numbers, names, and official emails.
        'require_roster' => (bool) env('STUDENT_REGISTRATION_REQUIRE_ROSTER', false),
    ],

    'rate_limiting' => [
        'web_requests_per_minute' => (int) env('WEB_RATE_LIMIT_PER_MINUTE', 120),
    ],

    'authentication' => [
        // Redis is recommended in production. Leave this null to use CACHE_STORE.
        'cache_store' => env('AUTH_SECURITY_CACHE_STORE'),
        'endpoint_attempts_per_minute' => (int) env('AUTH_ENDPOINT_ATTEMPTS_PER_MINUTE', 30),
        'minimum_form_fill_seconds' => (int) env('AUTH_MINIMUM_FORM_FILL_SECONDS', 2),
        'maximum_form_age_seconds' => (int) env('AUTH_MAXIMUM_FORM_AGE_SECONDS', 3600),
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
