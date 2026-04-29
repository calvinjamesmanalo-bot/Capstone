@extends('layouts.app')

@section('title', 'User Management')
@section('page_title', 'User Directory')
@section('page_subtitle', 'Manage all administrative and student accounts')

@section('content')
<div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">
    <div class="p-10 border-b border-slate-50 flex justify-between items-center bg-slate-50/50">
        <div class="flex items-center gap-6">
            <div class="w-16 h-16 bg-white rounded-2xl shadow-sm flex items-center justify-center text-indigo-600">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            </div>
            <div>
                <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tight">Active Accounts</h2>
                <p class="text-sm font-medium text-slate-400 mt-1">Total of 428 registered users</p>
            </div>
        </div>
        <a href="{{ route('users.create') }}" class="px-8 py-4 bg-indigo-600 text-white text-xs font-black rounded-2xl shadow-lg shadow-indigo-500/20 hover:bg-indigo-700 transition-all uppercase tracking-widest flex items-center gap-3">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Add New User
        </a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-slate-50 text-[10px] uppercase tracking-[0.3em] font-black text-slate-400 border-b border-slate-100">
                    <th class="px-10 py-6">Identity</th>
                    <th class="px-10 py-6">Role Assigned</th>
                    <th class="px-10 py-6">Status</th>
                    <th class="px-10 py-6">Last Login</th>
                    <th class="px-10 py-6 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse($users as $user)
                @php
                    $initials = collect(explode(' ', $user->display_name))->map(fn($n) => substr($n, 0, 1))->take(2)->join('');
                @endphp
                <tr class="hover:bg-slate-50/50 transition-all group">
                    <td class="px-10 py-8">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-indigo-50 rounded-2xl flex items-center justify-center text-indigo-600 font-black text-lg group-hover:bg-indigo-600 group-hover:text-white transition-all uppercase">
                                {{ $initials }}
                            </div>
                            <div>
                                <div class="text-sm font-black text-slate-800 tracking-tight">{{ $user->display_name }}</div>
                                <div class="text-[10px] font-black text-indigo-600 uppercase tracking-widest mt-1">{{ $user->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-10 py-8">
                        <span class="px-4 py-2 bg-slate-50 text-[10px] font-black text-slate-500 uppercase tracking-widest rounded-xl border border-slate-100 shadow-sm">
                            {{ $user->role ?? 'User' }}
                        </span>
                    </td>
                    <td class="px-10 py-8">
                        <div class="flex items-center gap-3">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shadow-[0_0_12px_rgba(16,185,129,0.5)]"></span>
                            <span class="text-[10px] font-black text-emerald-600 uppercase tracking-widest">Active</span>
                        </div>
                    </td>
                    <td class="px-10 py-8 text-sm font-bold text-slate-500">
                        {{ $user->updated_at->diffForHumans() }}
                    </td>
                    <td class="px-10 py-8 text-right">
                        <div class="flex justify-end gap-3">
                            <a href="{{ route('users.edit', $user) }}" class="w-10 h-10 flex items-center justify-center text-slate-400 hover:text-indigo-600 bg-white rounded-xl shadow-sm border border-slate-100 transition-all">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                            </a>
                            <form action="{{ route('users.destroy', $user) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this user?');" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-10 h-10 flex items-center justify-center text-slate-400 hover:text-red-600 bg-white rounded-xl shadow-sm border border-slate-100 transition-all">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-10 py-12 text-center">
                        <div class="flex flex-col items-center">
                            <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center text-slate-300 mb-4">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                            <p class="text-slate-400 font-bold">No users found in the system</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
