<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('code') - Fiat Lux Document Request Hub</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px; background: #f6f7fb; color: #0f172a; font-family: Inter, Arial, sans-serif; }
        main { width: min(560px, 100%); padding: 42px; border: 1px solid #e2e8f0; border-top: 6px solid #ffd22d; border-radius: 24px; background: white; text-align: center; box-shadow: 0 18px 50px rgba(15, 23, 42, .08); }
        .code { color: #000638; font-size: 64px; font-weight: 900; line-height: 1; }
        h1 { margin: 18px 0 10px; color: #000638; font-size: 26px; }
        p { margin: 0 auto 26px; max-width: 440px; color: #64748b; line-height: 1.65; }
        a { display: inline-block; padding: 12px 22px; border-radius: 12px; background: #000638; color: white; font-weight: 700; text-decoration: none; }
        small { display: block; margin-top: 22px; color: #94a3b8; }
    </style>
</head>
<body>
    <main>
        <div class="code">@yield('code')</div>
        <h1>@yield('title')</h1>
        <p>@yield('message')</p>
        <a href="{{ auth()->check() ? route('dashboard') : route('login') }}">{{ auth()->check() ? 'Back to dashboard' : 'Go to login' }}</a>
        <small>If this keeps happening, contact the system administrator.</small>
    </main>
</body>
</html>
