@extends('layouts.app')

@section('title', 'Settings')
@section('page_title', 'System Settings')
@section('page_subtitle', 'Configure application preferences and security')

@section('content')
<div class="max-w-4xl">

    @if($errors->any())<ul class="mb-5 rounded-xl bg-red-50 p-4 text-red-800" role="alert">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
    <form id="settings-form" action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data">
        @csrf
    </form>
    <div class="grid grid-cols-1 gap-6">
        {{-- ANNOUNCEMENTS --}}
           <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                <div class="flex items-center gap-4 border-b border-slate-200 px-5 py-5">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center text-[#000638]">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 11v2a2 2 0 002 2h1l2 5h3l-2-5h2l7 3V6l-7 3H5a2 2 0 00-2 2z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 10a3 3 0 010 4"/>
                        </svg>
                    </div>

                    <div>
                        <h2 class="text-xl font-semibold text-slate-900">Announcements</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            Create and manage announcements displayed on the student dashboard.
                        </p>
                    </div>
                </div>

                <div class="p-5">
                    @include('settings.partials.announcements')
                </div>
            </div>


            {{-- SCHOOL LOGO --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                <div class="flex items-center gap-4 border-b border-slate-200 px-5 py-5">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center text-[#000638]">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3l7 3v5c0 5-3 8-7 10-4-2-7-5-7-10V6l7-3z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 11l2 2 4-4"/>
                        </svg>
                    </div>

                    <div>
                        <h2 class="text-xl font-semibold text-slate-900">School Logo</h2>
                        <p class="mt-1 text-sm text-slate-500">Manage the school logo or seal displayed throughout the system.</p>
                    </div>
                </div>

                <div class="space-y-5 p-5">
                    <label for="school_logo" class="block text-sm font-semibold text-slate-800">
                        School logo/seal (PNG or JPG, up to 2 MB)

                        <input
                            form="settings-form"
                            id="school_logo"
                            name="school_logo"
                            type="file"
                            accept=".png,.jpg,.jpeg"
                            class="mt-2 block w-full rounded-lg border-2 border-slate-300 bg-white p-2 text-sm font-normal text-slate-600 transition file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-[#000638] hover:border-slate-400 file:hover:bg-slate-200 focus:border-[#000638] focus:outline-none focus:ring-1 focus:ring-[#000638]"
                        >
                    </label>

                    <div>
                        <p class="mb-2 text-sm font-semibold text-slate-800">Current School Logo</p>

                        <img
                            src="{{ \App\Support\SystemContent::logoUrl() }}"
                            alt="Current school logo"
                            class="h-20 w-20 object-contain"
                        >
                    </div>

                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input
                            form="settings-form"
                            type="checkbox"
                            name="remove_school_logo"
                            value="1"
                            class="h-4 w-4 rounded border-slate-300 text-[#000638] accent-[#000638] focus:ring-[#000638]"
                        >
                        Restore the default logo
                    </label>

                    <div class="flex justify-end">
                        <button
                            form="settings-form"
                            type="submit"
                            class="rounded-lg bg-[#000638] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#10175c] focus:outline-none focus:ring-2 focus:ring-[#000638] focus:ring-offset-2"
                        >
                            Save Settings
                        </button>
                    </div>
                </div>
            </div>
        <!-- General Settings -->
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <div class="flex items-center gap-4 border-b border-slate-200 px-5 py-5">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center text-[#000638]">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16M8 4v4m8 2v4M10 16v4" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">General Configuration</h2>
                    <p class="mt-1 text-sm text-slate-500">Basic system identity and preferences</p>
                </div>
            </div>
            <div class="p-5 space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label for="institution_name" class="block text-sm font-semibold text-slate-800">Institution Name</label>
                        <input form="settings-form" type="text" name="institution_name" id="institution_name" maxlength="120" value="{{ old('institution_name', $settings['institution_name'] ?? 'Fiat Lux Academe') }}"
                            class="w-full rounded-lg border-2 border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 outline-none transition hover:border-slate-400 focus:border-[#000638] focus:ring-1 focus:ring-[#000638]">
                        @error('institution_name')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="space-y-2">
                        <label for="system_email" class="block text-sm font-semibold text-slate-800">School Email</label>
                        <input form="settings-form" type="email" name="system_email" id="system_email" maxlength="120" value="{{ old('system_email', $settings['system_email'] ?? 'admin@fiatlux.edu.ph') }}"
                            class="w-full rounded-lg border-2 border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 outline-none transition hover:border-slate-400 focus:border-[#000638] focus:ring-1 focus:ring-[#000638]">
                        @error('system_email')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="space-y-2">
                        <label for="school_address" class="block text-sm font-semibold text-slate-800">School Address</label>
                        <input form="settings-form" type="text" name="school_address" id="school_address" maxlength="500" value="{{ old('school_address', $settings['school_address'] ?? '') }}"
                            class="w-full rounded-lg border-2 border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 outline-none transition hover:border-slate-400 focus:border-[#000638] focus:ring-1 focus:ring-[#000638]">
                        @error('school_address')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="space-y-2">
                        <label for="contact_number" class="block text-sm font-semibold text-slate-800">Landline Number</label>
                        <input form="settings-form" type="tel" name="contact_number" id="contact_number" maxlength="60" value="{{ old('contact_number', $settings['contact_number'] ?? '') }}"
                            class="w-full rounded-lg border-2 border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 outline-none transition hover:border-slate-400 focus:border-[#000638] focus:ring-1 focus:ring-[#000638]">
                        @error('contact_number')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="space-y-2">
                        <label for="mobile_number" class="block text-sm font-semibold text-slate-800">Mobile/Cellphone Number</label>
                        <input form="settings-form" type="tel" name="mobile_number" id="mobile_number" maxlength="60" value="{{ old('mobile_number', $settings['mobile_number'] ?? '') }}"
                            class="w-full rounded-lg border-2 border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 outline-none transition hover:border-slate-400 focus:border-[#000638] focus:ring-1 focus:ring-[#000638]">
                        @error('mobile_number')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="space-y-2">
                        <label for="office_hours" class="block text-sm font-semibold text-slate-800">Office Hours</label>
                        <input form="settings-form" type="text" name="office_hours" id="office_hours" maxlength="120" value="{{ old('office_hours', $settings['office_hours'] ?? 'Mon-Fri 8:00 AM - 5:00 PM') }}"
                            class="w-full rounded-lg border-2 border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 outline-none transition hover:border-slate-400 focus:border-[#000638] focus:ring-1 focus:ring-[#000638]">
                        @error('office_hours')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="flex justify-end">
                    <button form="settings-form" type="submit" class="rounded-lg bg-[#000638] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#10175c] focus:outline-none focus:ring-2 focus:ring-[#000638] focus:ring-offset-2">
                        Save Changes
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
