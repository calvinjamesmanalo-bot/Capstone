<?php

$useTurnstileTestKeys = env('APP_ENV', 'production') !== 'production'
    && filter_var(env('TURNSTILE_USE_TEST_KEYS', true), FILTER_VALIDATE_BOOL);

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'turnstile' => [
        'enabled' => env('TURNSTILE_ENABLED', true),
        'using_test_keys' => $useTurnstileTestKeys,
        'site_key' => $useTurnstileTestKeys
            ? env('TURNSTILE_TEST_SITE_KEY', '1x00000000000000000000AA')
            : env('TURNSTILE_SITE_KEY'),
        'secret_key' => $useTurnstileTestKeys
            ? env('TURNSTILE_TEST_SECRET_KEY', '1x0000000000000000000000000000000AA')
            : env('TURNSTILE_SECRET_KEY'),
        'verify_url' => env('TURNSTILE_VERIFY_URL', 'https://challenges.cloudflare.com/turnstile/v0/siteverify'),
        'expected_action' => $useTurnstileTestKeys ? 'test' : env('TURNSTILE_EXPECTED_ACTION', 'login'),
        'allowed_hostnames' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('TURNSTILE_ALLOWED_HOSTNAMES', ''))
        ))),
    ],

];
