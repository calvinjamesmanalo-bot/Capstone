<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()?->role === 'registrar', 403);

        $settings = Setting::all()->pluck('value', 'key');

        $announcements = \App\Models\Announcement::latest('id')->get();

        return view('settings.index', compact('settings', 'announcements'));
    }

    public function update(Request $request)
    {
        abort_unless(auth()->user()?->role === 'registrar', 403);

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
        ]);

        // Persist only validated public settings, never arbitrary submitted keys or credentials.
        if ($request->has('maintenance_mode')) {
            $data['maintenance_mode'] = $request->boolean('maintenance_mode') ? '1' : '0';
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
                        $key === 'student_idle_timeout_minutes' => 'security',
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
}
