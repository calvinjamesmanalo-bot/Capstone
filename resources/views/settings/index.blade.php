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

        <!-- System Status -->
        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="p-8 border-b border-slate-50 bg-slate-50/50">
                <h3 class="font-black text-slate-800 text-lg">System Status</h3>
                <p class="text-xs font-medium text-slate-400 mt-1">Control system availability and maintenance</p>
            </div>
            <div class="p-8 space-y-4">
                <div class="flex items-center justify-between p-6 bg-indigo-50/50 rounded-2xl border border-indigo-100/50">
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
                    <button type="submit" class="px-8 py-4 bg-indigo-600 text-white text-xs font-black rounded-2xl shadow-xl shadow-indigo-500/10 hover:bg-indigo-700 transition-all uppercase tracking-[0.2em]">
                        Update Status
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
