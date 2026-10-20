@extends('layouts.app')
@section('title', 'Account Creation')
@section('page_title', 'Account Creation')
@section('page_subtitle', 'Upload an official PDF and create student login accounts')
@section('content')
<div class="space-y-6">
    <nav class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm sm:grid-cols-2" aria-label="Account creation sections">
        <a href="{{ route('account-creation.index') }}" class="rounded-xl bg-[#000638] px-5 py-4 text-center font-bold text-white">Upload PDF</a>
        <a href="{{ route('account-creation.accounts') }}" class="rounded-xl border border-slate-300 px-5 py-4 text-center font-bold text-[#000638] hover:bg-slate-50">View Created Accounts</a>
    </nav>
    @if(session('success'))<div class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>@endif
    @if(session('masterlist_account_results'))
        @php($accountResults = session('masterlist_account_results'))
        <section class="rounded-2xl border-2 border-emerald-300 bg-white p-6 shadow-sm">
            <p class="text-sm font-bold text-emerald-700">Upload complete</p><h2 class="mt-1 text-2xl font-black text-[#000638]">Account creation results</h2>
            <div class="mt-5 grid gap-3 sm:grid-cols-4">
                <div class="rounded-xl bg-blue-50 p-4"><p class="text-sm font-bold text-blue-700">PDF records</p><p class="mt-1 text-3xl font-black text-blue-950">{{ number_format($accountResults['processed']) }}</p></div>
                <div class="rounded-xl bg-emerald-50 p-4"><p class="text-sm font-bold text-emerald-700">Accounts created</p><p class="mt-1 text-3xl font-black text-emerald-950">{{ number_format($accountResults['created']) }}</p></div>
                <div class="rounded-xl bg-amber-50 p-4"><p class="text-sm font-bold text-amber-800">Existing skipped</p><p class="mt-1 text-3xl font-black text-amber-950">{{ number_format($accountResults['skipped']) }}</p></div>
                <div class="rounded-xl bg-red-50 p-4"><p class="text-sm font-bold text-red-700">Failed</p><p class="mt-1 text-3xl font-black text-red-950">{{ number_format($accountResults['failed']) }}</p></div>
            </div>
            <a href="{{ route('account-creation.accounts') }}" class="mt-5 inline-flex rounded-xl bg-[#000638] px-5 py-3 font-bold text-white">Open Created Accounts</a>
        </section>
    @endif
    <section class="grid gap-5 lg:grid-cols-[1.35fr_.65fr]">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-sm font-bold text-[#b28100]">Create accounts from PDF</p><h2 class="mt-1 text-2xl font-bold text-[#000638]">Upload student PDF</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">Each new student receives a secure random temporary password. Existing accounts are skipped. Students must request their temporary password directly from the Registrar.</p>
            <form action="{{ route('account-creation.store') }}" method="POST" enctype="multipart/form-data" class="mt-5" data-masterlist-upload>
                @csrf
                <label for="masterlist_pdf" class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-blue-300 bg-blue-50 px-5 py-8 text-center hover:border-blue-500">
                    <svg class="h-9 w-9 text-[#000638]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 16V4m0 0-4 4m4-4 4 4M4 15v4a1 1 0 001 1h14a1 1 0 001-1v-4"/></svg>
                    <span id="masterlist_file_name" class="mt-3 font-bold text-[#000638]">Choose Student List PDF</span><span class="mt-1 text-xs text-slate-500">Maximum file size: 20 MB</span>
                </label>
                <input id="masterlist_pdf" name="masterlist_pdf" type="file" accept="application/pdf,.pdf" required class="sr-only">
                @error('masterlist_pdf')<p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p>@enderror
                <button type="submit" class="mt-4 w-full rounded-xl bg-[#000638] px-5 py-3 font-bold text-white hover:bg-[#10175a]">Upload PDF and create accounts</button>
            </form>
            @if($imports->isNotEmpty())<form method="POST" action="{{ route('account-creation.purge') }}" class="mt-3" onsubmit="return confirm('Delete ALL uploaded PDFs and generated student accounts? Grades and academic records will remain.')">@csrf @method('DELETE')<input type="hidden" name="confirm_purge" value="1"><button class="w-full rounded-xl border-2 border-red-300 bg-red-50 px-5 py-3 font-bold text-red-700 hover:bg-red-100">Delete all uploaded account data</button></form>@endif
        </div>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-1">
            <a href="{{ route('account-creation.accounts') }}" class="rounded-2xl border border-blue-200 bg-blue-50 p-6 hover:border-blue-400"><p class="text-sm font-semibold text-blue-700">Created accounts</p><p class="mt-2 text-4xl font-black text-[#000638]">{{ number_format($total) }}</p><p class="mt-2 text-sm font-bold text-blue-700">View account details →</p></a>
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6"><p class="text-sm font-semibold text-amber-800">Records without LRN</p><p class="mt-2 text-4xl font-black text-amber-900">{{ number_format($missingLrn) }}</p></div>
        </div>
    </section>
    @if($imports->isNotEmpty())<section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><h2 class="text-lg font-bold text-[#000638]">Upload history</h2><div class="mt-4 space-y-3">@foreach($imports as $import)<div class="flex flex-col gap-2 rounded-xl border border-slate-200 p-4 sm:flex-row sm:items-center sm:justify-between"><div><p class="font-bold">{{ $import->original_name }}</p><p class="text-xs text-slate-500">SY {{ $import->school_year }} · {{ $import->created_at->format('M d, Y h:i A') }}</p></div><div class="text-sm"><strong>{{ number_format($import->imported_rows) }}</strong> processed · <span class="text-emerald-700">{{ number_format($import->new_rows) }} new</span> · <span class="text-blue-700">{{ number_format($import->reused_rows) }} existing skipped</span></div></div>@endforeach</div></section>@endif
</div>
<script>document.getElementById('masterlist_pdf')?.addEventListener('change', function(){ document.getElementById('masterlist_file_name').textContent = this.files[0]?.name || 'Choose Student List PDF'; });</script>
@endsection
