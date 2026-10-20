@extends('layouts.app')
@section('title', 'Created Accounts')
@section('page_title', 'Created Accounts')
@section('page_subtitle', 'Student accounts generated from uploaded PDF lists')
@section('content')
<div class="space-y-6">
    <nav class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm sm:grid-cols-2" aria-label="Account creation sections">
        <a href="{{ route('account-creation.index') }}" class="rounded-xl border border-slate-300 px-5 py-4 text-center font-bold text-[#000638] hover:bg-slate-50">Upload PDF</a>
        <a href="{{ route('account-creation.accounts') }}" class="rounded-xl bg-[#000638] px-5 py-4 text-center font-bold text-white">Created Accounts</a>
    </nav>

    @if(session('success'))<div class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>@endif
    @if(session('reissued_student_credential'))
        @php($reissued = session('reissued_student_credential'))
        <section class="rounded-2xl border-2 border-amber-300 bg-amber-50 p-6 shadow-sm"><p class="text-sm font-bold text-amber-800">New temporary password issued</p><h2 class="mt-1 text-xl font-black text-[#000638]">{{ $reissued['name'] }}</h2><div class="mt-4 grid gap-3 sm:grid-cols-2"><div class="rounded-xl bg-white p-4"><p class="text-xs font-bold text-slate-500">Student Number</p><p class="mt-1 font-mono text-lg font-black">{{ $reissued['student_number'] }}</p></div><div class="rounded-xl bg-white p-4"><p class="text-xs font-bold text-slate-500">Temporary Password</p><p class="mt-1 font-mono text-lg font-black">{{ $reissued['temporary_password'] }}</p></div></div></section>
    @endif

    <section class="rounded-2xl border border-blue-200 bg-blue-50 p-5">
        <h2 class="font-bold text-blue-950">About passwords</h2>
        <p class="mt-1 text-sm leading-6 text-blue-800">Temporary passwords are random and never stored in readable form. Use “Issue password” when a student requests access, then give the one-time password directly to the student before leaving the page.</p>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('account-creation.accounts') }}" class="grid gap-3 lg:grid-cols-[2fr_1fr_1fr_1fr_auto]">
            <input name="search" value="{{ request('search') }}" placeholder="Search Student Number, LRN, or name" class="rounded-xl border-slate-300 px-4 py-3 text-sm">
            <select name="school_year" class="rounded-xl border-slate-300 text-sm"><option value="">All school years</option>@foreach($schoolYears as $value)<option value="{{ $value }}" @selected(request('school_year') === $value)>{{ $value }}</option>@endforeach</select>
            <select name="campus" class="rounded-xl border-slate-300 text-sm"><option value="">All campuses</option>@foreach($campuses as $value)<option value="{{ $value }}" @selected(request('campus') === $value)>{{ $value }}</option>@endforeach</select>
            <select name="grade_level" class="rounded-xl border-slate-300 text-sm"><option value="">All grade levels</option>@foreach($gradeLevels as $value)<option value="{{ $value }}" @selected(request('grade_level') === $value)>{{ $value }}</option>@endforeach</select>
            <button class="rounded-xl bg-blue-600 px-5 py-3 text-sm font-bold text-white">Filter</button>
        </form>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4"><h2 class="text-xl font-bold text-[#000638]">All Created Accounts</h2><p class="text-sm text-slate-500">{{ number_format($students->total()) }} account(s) found</p></div>
        <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-bold text-slate-600"><tr><th class="px-5 py-3">Student</th><th class="px-5 py-3">Password Status</th><th class="px-5 py-3">Grade & Section</th><th class="px-5 py-3">Actions</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($students as $student)
                @php($account = $student->user)
                <tr><td class="px-5 py-4"><p class="font-bold text-slate-900">{{ $student->name }}</p><p class="mt-1 font-mono text-xs text-slate-500">{{ $student->student_number }} @if($student->lrn) · LRN {{ $student->lrn }} @endif</p></td>
                    <td class="px-5 py-4">@if($account?->must_change_password)<span class="font-bold text-amber-700">Temporary — must change</span>@else<span class="font-bold text-emerald-700">Changed by student</span>@endif</td>
                    <td class="px-5 py-4"><p class="font-semibold">{{ $student->masterlist_grade_level ?: 'Not detected' }}</p><p class="text-xs text-slate-500">{{ $student->masterlist_section ?: 'No section' }} · SY {{ $student->masterlist_school_year }}</p></td>
                    <td class="px-5 py-4"><div class="flex flex-wrap gap-2"><form method="POST" action="{{ route('account-creation.reissue-password', $student) }}" onsubmit="return confirm('Issue a new random temporary password? All signed-in sessions and the current password will stop working.')">@csrf<button class="rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-xs font-bold text-amber-900">Issue password</button></form><form method="POST" action="{{ route('account-creation.destroy-account', $student) }}" onsubmit="return confirm('Move this account to Archived Accounts for 6 months?')">@csrf @method('DELETE')<input type="hidden" name="confirm_delete" value="1"><button class="rounded-lg border border-red-300 bg-red-50 px-3 py-2 text-xs font-bold text-red-700">Archive account</button></form></div></td></tr>
            @empty<tr><td colspan="4" class="px-5 py-12 text-center text-slate-500">No created accounts found.</td></tr>@endforelse
            </tbody>
        </table></div>
        <div class="border-t border-slate-200 px-5 py-4">{{ $students->links() }}</div>
    </section>
</div>
@endsection
