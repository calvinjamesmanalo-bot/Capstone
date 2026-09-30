<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function index()
    {
        $role = auth()->user()?->role;
        abort_unless(in_array($role, ['admin', 'registrar'], true), 403);

        $settings = Setting::all()->pluck('value', 'key');
        $announcements = $role === 'registrar'
            ? \App\Models\Announcement::latest('id')->get()
            : collect();

        $backups = $role === 'admin'
            ? collect(File::glob(storage_path('app/backups/*.zip')))->map(fn ($path) => [
                'name' => basename($path), 'size' => File::size($path), 'created_at' => File::lastModified($path),
            ])->sortByDesc('created_at')->take(10)->values()
            : collect();
        $health = $role === 'admin' ? [
            'database' => DB::connection()->getPdo() ? 'Operational' : 'Unavailable',
            'mail' => $this->mailStatus(),
            'queue' => config('queue.default') === 'sync' ? 'Synchronous' : 'Asynchronous',
            'storage' => is_writable(storage_path()) ? 'Writable' : 'Read only',
            'storage_bytes' => collect(File::allFiles(storage_path('app')))->sum(fn ($file) => $file->getSize()),
        ] : [];

        return view('settings.index', compact('settings', 'announcements', 'backups', 'health'));
    }

    public function update(Request $request)
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $data = $request->validate([
            'school_address' => ['nullable', 'string', 'max:500'],
            'school_logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048', 'dimensions:max_width=3000,max_height=3000'],
            'remove_school_logo' => ['nullable', 'boolean'],
            'institution_name' => ['nullable', 'string', 'max:120'],
            'system_email' => ['nullable', 'email', 'max:120'],
            // Keep the existing contact key for the landline to preserve saved numbers.
            'contact_number' => ['nullable', 'string', 'max:60'],
            'mobile_number' => ['nullable', 'string', 'max:60'],
            'office_hours' => ['nullable', 'string', 'max:120'],
            'student_idle_timeout_minutes' => ['sometimes', 'required', 'integer', 'min:1', 'max:120'],
            'maintenance_mode' => ['nullable', 'boolean'],
            'maintenance_message' => ['nullable', 'string', 'max:500'],
            'login_max_attempts' => ['sometimes', 'required', 'integer', 'min:1', 'max:20'],
            'login_lockout_minutes' => ['sometimes', 'required', 'integer', 'min:1', 'max:1440'],
            'portal_primary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'portal_accent_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'portal_footer_text' => ['nullable', 'string', 'max:200'],
            'student_registration_enabled' => ['nullable', 'boolean'],
            'student_email_verification_required' => ['nullable', 'boolean'],
            'document_requests_enabled' => ['nullable', 'boolean'],
            'grade_uploads_enabled' => ['nullable', 'boolean'],
            'public_verification_enabled' => ['nullable', 'boolean'],
        ]);

        // Persist only validated public settings, never arbitrary submitted keys or credentials.
        foreach (['maintenance_mode', 'student_registration_enabled', 'student_email_verification_required', 'document_requests_enabled', 'grade_uploads_enabled', 'public_verification_enabled'] as $toggle) {
            if ($request->has($toggle)) {
                $data[$toggle] = $request->boolean($toggle) ? '1' : '0';
            }
        }
        $oldLogo = Setting::where('key', 'school_logo_path')->value('value');
        $newLogo = null;
        if ($request->hasFile('school_logo')) {
            $newLogo = $request->file('school_logo')->store('school-branding', 'local');
            abort_unless($newLogo, 500, 'Unable to save school logo.');
            $data['school_logo_path'] = $newLogo;
        } elseif ($request->boolean('remove_school_logo')) {
            $data['school_logo_path'] = null;
        }
        unset($data['school_logo'], $data['remove_school_logo']);

        try {
            DB::transaction(function () use ($data) {
                foreach ($data as $key => $value) {
                    $group = match (true) {
                        in_array($key, ['student_idle_timeout_minutes', 'login_max_attempts', 'login_lockout_minutes'], true) => 'security',
                        str_ends_with($key, '_enabled') => 'features',
                        str_starts_with($key, 'portal_') => 'branding',
                        default => 'general',
                    };

                    Setting::updateOrCreate(
                        ['key' => $key],
                        ['value' => $value, 'group' => $group]
                    );
                }
            });
        } catch (\Throwable $exception) {
            if ($newLogo) {
                Storage::disk('local')->delete($newLogo);
            }
            throw $exception;
        }
        if (array_key_exists('school_logo_path', $data) && $oldLogo && str_starts_with($oldLogo, 'school-branding/') && ! str_contains($oldLogo, '..')) {
            Storage::disk('local')->delete($oldLogo);
        }

        record_log('Updated System Settings', 'Settings', 'Modified system-wide configurations');

        return redirect()->back()->with('success', 'Settings updated successfully.');
    }

    private function mailStatus(): string
    {
        $mailer = (string) config('mail.default');
        if ($mailer === 'log') {
            return 'Log only';
        }
        if ($mailer !== 'smtp') {
            return 'Configured';
        }

        $username = (string) config('mail.mailers.smtp.username');
        $password = (string) config('mail.mailers.smtp.password');

        return $username === '' || $password === '' || preg_match('/your-|example|change[-_ ]?me/i', $username)
            ? 'Setup required'
            : 'Configured';
    }
}
