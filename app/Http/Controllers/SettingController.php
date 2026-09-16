<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $settings = Setting::all()->pluck('value', 'key');

        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $request->validate([
            'institution_name' => ['nullable', 'string', 'max:120'],
            'system_email' => ['nullable', 'email', 'max:120'],
            'contact_number' => ['nullable', 'string', 'max:60'],
            'office_hours' => ['nullable', 'string', 'max:120'],
            'school_name' => ['nullable', 'string', 'max:160'],
            'school_district' => ['nullable', 'string', 'max:120'],
            'school_id' => ['nullable', 'string', 'max:40'],
            'school_division' => ['nullable', 'string', 'max:120'],
            'school_region' => ['nullable', 'string', 'max:120'],
            'student_idle_timeout_minutes' => ['required', 'integer', 'min:1', 'max:120'],
            'maintenance_mode' => ['nullable', 'boolean'],
            'price_certificate_enrollment' => ['required', 'numeric', 'min:100', 'max:150'],
            'price_certificate_completion' => ['required', 'numeric', 'min:100', 'max:150'],
            'price_good_moral' => ['required', 'numeric', 'min:100', 'max:150'],
            'price_certificate_recognition' => ['required', 'numeric', 'min:100', 'max:150'],
            'price_diploma' => ['required', 'numeric', 'min:100', 'max:150'],
        ]);

        $data = $request->except('_token');

        foreach ($data as $key => $value) {
            $group = match (true) {
                str_starts_with($key, 'price_') => 'pricing',
                in_array($key, ['school_name', 'school_district', 'school_id', 'school_division', 'school_region'], true) => 'school_profile',
                $key === 'student_idle_timeout_minutes' => 'security',
                default => 'general',
            };

            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'group' => $group]
            );
        }

        record_log('Updated System Settings', 'Settings', 'Modified system-wide configurations');

        return redirect()->back()->with('success', 'Settings updated successfully.');
    }
}
