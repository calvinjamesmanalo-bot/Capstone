@php($field = 'w-full rounded-lg border-2 border-slate-300 bg-white px-3 py-2 text-sm')
<section class="rounded-2xl border border-slate-200 bg-white p-5 space-y-5">
    <div><h2 class="text-xl font-semibold text-slate-900">Maintenance &amp; Feature Controls</h2><p class="text-sm text-slate-500">Control portal availability and major public functions.</p></div>
    <input form="settings-form" type="hidden" name="maintenance_mode" value="0"><label class="flex gap-3"><input form="settings-form" type="checkbox" name="maintenance_mode" value="1" @checked(($settings['maintenance_mode'] ?? '0') === '1')> <span><strong>Maintenance mode</strong><span class="block text-sm text-slate-500">Administrators retain access.</span></span></label>
    <label class="block text-sm font-semibold">Maintenance notice<textarea form="settings-form" name="maintenance_message" maxlength="500" rows="2" class="{{ $field }} mt-2">{{ old('maintenance_message', $settings['maintenance_message'] ?? 'The portal is temporarily unavailable for maintenance.') }}</textarea></label>
    <div class="grid gap-3 sm:grid-cols-2">
        @foreach(['student_registration_enabled' => 'Student registration', 'document_requests_enabled' => 'Document requests', 'grade_uploads_enabled' => 'Grade uploads', 'public_verification_enabled' => 'Public document verification'] as $key => $label)
            <label class="flex items-center gap-3 rounded-xl border p-3"><input form="settings-form" type="hidden" name="{{ $key }}" value="0"><input form="settings-form" type="checkbox" name="{{ $key }}" value="1" @checked(($settings[$key] ?? '1') === '1')><span class="text-sm font-semibold">{{ $label }}</span></label>
        @endforeach
    </div>
</section>

<section class="rounded-2xl border border-slate-200 bg-white p-5 space-y-5">
    <div><h2 class="text-xl font-semibold text-slate-900">Session &amp; Security</h2><p class="text-sm text-slate-500">Administrator-managed login and session limits.</p></div>
    <div class="grid gap-4 md:grid-cols-3">
        <label class="text-sm font-semibold">Student idle timeout (minutes)<input form="settings-form" type="number" name="student_idle_timeout_minutes" min="1" max="120" value="{{ old('student_idle_timeout_minutes', $settings['student_idle_timeout_minutes'] ?? 15) }}" class="{{ $field }} mt-2"></label>
        <label class="text-sm font-semibold">Maximum login attempts<input form="settings-form" type="number" name="login_max_attempts" min="1" max="20" value="{{ old('login_max_attempts', $settings['login_max_attempts'] ?? 5) }}" class="{{ $field }} mt-2"></label>
        <label class="text-sm font-semibold">Initial lockout (minutes)<input form="settings-form" type="number" name="login_lockout_minutes" min="1" max="1440" value="{{ old('login_lockout_minutes', $settings['login_lockout_minutes'] ?? 15) }}" class="{{ $field }} mt-2"></label>
    </div>
</section>

<section class="rounded-2xl border border-slate-200 bg-white p-5 space-y-5">
    <div><h2 class="text-xl font-semibold text-slate-900">Branding</h2><p class="text-sm text-slate-500">Website colors and footer identity.</p></div>
    <div class="grid gap-4 md:grid-cols-2">
        <label class="text-sm font-semibold">Primary color<input form="settings-form" type="color" name="portal_primary_color" value="{{ old('portal_primary_color', $settings['portal_primary_color'] ?? '#000638') }}" class="mt-2 h-11 w-full rounded border"></label>
        <label class="text-sm font-semibold">Accent color<input form="settings-form" type="color" name="portal_accent_color" value="{{ old('portal_accent_color', $settings['portal_accent_color'] ?? '#ffd22d') }}" class="mt-2 h-11 w-full rounded border"></label>
    </div>
    <label class="block text-sm font-semibold">Footer text<input form="settings-form" name="portal_footer_text" maxlength="200" value="{{ old('portal_footer_text', $settings['portal_footer_text'] ?? '') }}" class="{{ $field }} mt-2"></label>
</section>

<section class="rounded-2xl border border-slate-200 bg-white p-5 space-y-4">
    <div><h2 class="text-xl font-semibold text-slate-900">System Health &amp; Storage</h2><p class="text-sm text-slate-500">Current operational status of core services.</p></div>
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
        @foreach(['database' => 'Database', 'mail' => 'Email', 'queue' => 'Queue', 'storage' => 'Storage'] as $key => $label)<div class="rounded-xl border p-3"><span class="text-xs text-slate-500">{{ $label }}</span><strong class="block text-sm">{{ $health[$key] }}</strong></div>@endforeach
        <div class="rounded-xl border p-3"><span class="text-xs text-slate-500">Private files</span><strong class="block text-sm">{{ number_format($health['storage_bytes'] / 1048576, 2) }} MB</strong></div>
    </div>
    <form method="POST" action="{{ route('settings.test-email') }}" class="flex flex-col gap-3 sm:flex-row">@csrf<input type="email" name="test_email" required placeholder="admin@example.com" class="{{ $field }}"><button class="rounded-lg bg-[#000638] px-4 py-2 text-sm font-semibold text-white">Send Test Email</button></form>
</section>

<section class="rounded-2xl border border-slate-200 bg-white p-5 space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3"><div><h2 class="text-xl font-semibold text-slate-900">Backup Management</h2><p class="text-sm text-slate-500">Private database and uploaded-file archives.</p></div><form method="POST" action="{{ route('settings.backup') }}">@csrf<button class="rounded-lg bg-[#000638] px-4 py-2 text-sm font-semibold text-white">Create Backup</button></form></div>
    <div class="divide-y">@forelse($backups as $backup)<div class="flex items-center justify-between py-3 text-sm"><span>{{ $backup['name'] }} <small class="text-slate-500">({{ number_format($backup['size'] / 1048576, 2) }} MB)</small></span><a class="font-semibold text-indigo-700" href="{{ route('settings.backup.download', $backup['name']) }}">Download</a></div>@empty<p class="text-sm text-slate-500">No backups created yet.</p>@endforelse</div>
</section>

<div class="flex justify-end"><button form="settings-form" type="submit" class="rounded-lg bg-[#000638] px-6 py-3 text-sm font-semibold text-white">Save All Admin Settings</button></div>
