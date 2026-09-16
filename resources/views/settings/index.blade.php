@extends('layouts.app')

@section('title', 'Settings')
@section('page_title', 'System Settings')
@section('page_subtitle', 'Configure application preferences and security')

@section('content')
<div class="max-w-4xl">
    @if(session('success'))
    <div class="mb-6 p-4 bg-emerald-50 border border-emerald-100 text-emerald-600 rounded-2xl flex items-center gap-3">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
        <p class="text-sm font-bold">{{ session('success') }}</p>
    </div>
    @endif

    <form action="{{ route('settings.update') }}" method="POST" class="grid grid-cols-1 gap-8">
        @csrf
        <!-- General Settings -->
        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="p-8 border-b border-slate-50 bg-slate-50/50">
                <h3 class="font-black text-slate-800 text-lg">General Configuration</h3>
                <p class="text-xs font-medium text-slate-400 mt-1">Basic system identity and preferences</p>
            </div>
            <div class="p-8 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label for="institution_name" class="text-xs font-black text-slate-500 uppercase tracking-widest px-1">Institution Name</label>
                        <input type="text" name="institution_name" id="institution_name" value="{{ $settings['institution_name'] ?? 'Fiat Lux Academe' }}" 
                            class="w-full px-5 py-4 bg-slate-50 border-none rounded-2xl text-sm font-bold text-slate-800 focus:ring-2 focus:ring-indigo-500 transition-all">
                    </div>
                    <div class="space-y-2">
                        <label for="system_email" class="text-xs font-black text-slate-500 uppercase tracking-widest px-1">System Email</label>
                        <input type="email" name="system_email" id="system_email" value="{{ $settings['system_email'] ?? 'admin@fiatlux.edu.ph' }}" 
                            class="w-full px-5 py-4 bg-slate-50 border-none rounded-2xl text-sm font-bold text-slate-800 focus:ring-2 focus:ring-indigo-500 transition-all">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label for="contact_number" class="text-xs font-black text-slate-500 uppercase tracking-widest px-1">Contact Number</label>
                        <input type="text" name="contact_number" id="contact_number" value="{{ $settings['contact_number'] ?? '+63 (046) 431 1234' }}" 
                            class="w-full px-5 py-4 bg-slate-50 border-none rounded-2xl text-sm font-bold text-slate-800 focus:ring-2 focus:ring-indigo-500 transition-all">
                    </div>
                    <div class="space-y-2">
                        <label for="office_hours" class="text-xs font-black text-slate-500 uppercase tracking-widest px-1">Office Hours</label>
                        <input type="text" name="office_hours" id="office_hours" value="{{ $settings['office_hours'] ?? 'Mon-Fri 8:00 AM - 5:00 PM' }}" 
                            class="w-full px-5 py-4 bg-slate-50 border-none rounded-2xl text-sm font-bold text-slate-800 focus:ring-2 focus:ring-indigo-500 transition-all">
                    </div>
                </div>
                
                <div class="pt-4">
                    <button type="submit" class="px-8 py-4 bg-slate-900 text-white text-xs font-black rounded-2xl shadow-xl shadow-slate-900/10 hover:bg-slate-800 transition-all uppercase tracking-[0.2em]">
                        Save Changes
                    </button>
                </div>
            </div>
        </div>

        <!-- Student Session Security -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-6 border-b border-slate-200 bg-slate-50">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl bg-[#000638] text-[#ffd22d] flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-[#000638] text-lg">Student Session Security</h3>
                        <p class="text-sm text-slate-500 mt-1">Control how long an inactive student stays signed in.</p>
                    </div>
                </div>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 gap-5 md:grid-cols-[minmax(0,1fr)_260px] md:items-end">
                    <div>
                        <label for="student_idle_timeout_minutes" class="block text-sm font-semibold text-slate-700">Log out students after</label>
                        <p class="mt-1 text-sm text-slate-500">Clicking, typing, or scrolling resets the timer. Students receive a warning before automatic logout.</p>
                    </div>
                    <div>
                        <div class="relative">
                            <input type="number" name="student_idle_timeout_minutes" id="student_idle_timeout_minutes"
                                value="{{ old('student_idle_timeout_minutes', $settings['student_idle_timeout_minutes'] ?? 15) }}"
                                min="1" max="120" step="1" required
                                class="w-full rounded-xl border border-slate-300 bg-white py-3 pl-4 pr-24 text-base font-bold text-[#000638] focus:border-[#000638] focus:ring-2 focus:ring-[#ffd22d]/40">
                            <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4 text-sm font-semibold text-slate-500">minutes</span>
                        </div>
                        @error('student_idle_timeout_minutes')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="mt-5 flex flex-col gap-3 rounded-xl border border-blue-200 bg-blue-50 p-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-blue-800"><strong>Recommended:</strong> 15 minutes for shared school computers. The new time applies when a student loads their next page.</p>
                    <button type="submit" class="shrink-0 rounded-xl bg-[#000638] px-5 py-3 text-sm font-bold text-white transition hover:bg-[#10175a]">Save session timeout</button>
                </div>
            </div>
        </div>

        <!-- F137 School Profile -->
        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="p-8 border-b border-slate-50 bg-slate-50/50">
                <h3 class="font-black text-slate-800 text-lg">F137 School Profile</h3>
                <p class="text-xs font-medium text-slate-400 mt-1">These details will automatically fill every Scholastic Record section in generated F137 files.</p>
            </div>
            <div class="p-8 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2 md:col-span-2">
                        <label for="school_name" class="text-xs font-black text-slate-500 uppercase tracking-widest px-1">School</label>
                        <input type="text" name="school_name" id="school_name" value="{{ old('school_name', $settings['school_name'] ?? $settings['institution_name'] ?? 'Fiat Lux Academe') }}" maxlength="160"
                            class="w-full px-5 py-4 bg-slate-50 border-none rounded-2xl text-sm font-bold text-slate-800 focus:ring-2 focus:ring-indigo-500 transition-all">
                        @error('school_name')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="space-y-2">
                        <label for="school_district" class="text-xs font-black text-slate-500 uppercase tracking-widest px-1">District</label>
                        <input type="text" name="school_district" id="school_district" value="{{ old('school_district', $settings['school_district'] ?? '') }}" maxlength="120"
                            class="w-full px-5 py-4 bg-slate-50 border-none rounded-2xl text-sm font-bold text-slate-800 focus:ring-2 focus:ring-indigo-500 transition-all">
                        @error('school_district')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="space-y-2">
                        <label for="school_id" class="text-xs font-black text-slate-500 uppercase tracking-widest px-1">School ID</label>
                        <input type="text" name="school_id" id="school_id" value="{{ old('school_id', $settings['school_id'] ?? '') }}" maxlength="40"
                            class="w-full px-5 py-4 bg-slate-50 border-none rounded-2xl text-sm font-bold text-slate-800 focus:ring-2 focus:ring-indigo-500 transition-all">
                        @error('school_id')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="space-y-2">
                        <label for="school_division" class="text-xs font-black text-slate-500 uppercase tracking-widest px-1">Division</label>
                        <input type="text" name="school_division" id="school_division" value="{{ old('school_division', $settings['school_division'] ?? '') }}" maxlength="120"
                            class="w-full px-5 py-4 bg-slate-50 border-none rounded-2xl text-sm font-bold text-slate-800 focus:ring-2 focus:ring-indigo-500 transition-all">
                        @error('school_division')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="space-y-2">
                        <label for="school_region" class="text-xs font-black text-slate-500 uppercase tracking-widest px-1">Region</label>
                        <input type="text" name="school_region" id="school_region" value="{{ old('school_region', $settings['school_region'] ?? '') }}" maxlength="120"
                            class="w-full px-5 py-4 bg-slate-50 border-none rounded-2xl text-sm font-bold text-slate-800 focus:ring-2 focus:ring-indigo-500 transition-all">
                        @error('school_region')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full rounded-2xl bg-blue-600 px-8 py-4 text-xs font-black text-white shadow-xl shadow-blue-600/10 transition-all hover:bg-blue-700 sm:w-auto">
                        Save School Profile
                    </button>
                </div>
            </div>
        </div>

        <!-- Document Pricing -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-6 border-b border-slate-200 bg-slate-50">
                <h3 class="font-bold text-[#000638] text-lg">Document price list</h3>
                <p class="text-sm text-slate-500 mt-1">Set each document fee from ₱100 to ₱150.</p>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                @foreach([
                    'price_certificate_enrollment' => ['Certificate of Enrollment', 100],
                    'price_certificate_completion' => ['Certificate of Completion', 120],
                    'price_good_moral' => ['Certificate of Good Moral Character', 100],
                    'price_certificate_recognition' => ['Certificate of Recognition', 120],
                    'price_diploma' => ['Diploma', 150],
                ] as $key => [$label, $default])
                    <div class="space-y-2">
                        <label for="{{ $key }}" class="text-sm font-semibold text-slate-700">{{ $label }}</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 font-bold text-slate-500">₱</span>
                            <input type="number" name="{{ $key }}" id="{{ $key }}" value="{{ old($key, $settings[$key] ?? $default) }}" min="100" max="150" step="1" required
                                class="w-full rounded-lg border border-slate-300 bg-white py-3 pl-9 pr-4 text-sm font-semibold text-[#000638] focus:border-[#000638] focus:ring-2 focus:ring-[#ffd22d]/40">
                        </div>
                        @error($key)<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                @endforeach
            </div>
        </div>

        <!-- System Status -->
        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="p-8 border-b border-slate-50 bg-slate-50/50">
                <h3 class="font-black text-slate-800 text-lg">System Status</h3>
                <p class="text-xs font-medium text-slate-400 mt-1">Control system availability and maintenance</p>
            </div>
            <div class="p-8 space-y-4">
                <div class="flex items-start justify-between gap-4 p-6 bg-indigo-50/50 rounded-2xl border border-indigo-100/50 sm:items-center">
                    <div class="flex gap-4 items-center">
                        <div class="w-12 h-12 bg-indigo-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-indigo-500/20">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-black text-slate-800">Maintenance Mode</h4>
                            <p class="text-[11px] text-slate-500 font-medium">Disable public access during updates</p>
                        </div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="maintenance_mode" value="1" class="sr-only peer" {{ ($settings['maintenance_mode'] ?? '0') == '1' ? 'checked' : '' }}>
                        <div class="w-14 h-8 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[4px] after:start-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-indigo-600"></div>
                    </label>
                </div>
                
                <div class="pt-4">
                    <button type="submit" class="w-full rounded-2xl bg-indigo-600 px-8 py-4 text-xs font-black text-white shadow-xl shadow-indigo-500/10 transition-all hover:bg-indigo-700 sm:w-auto">
                        Update Status
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
