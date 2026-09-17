@extends('layouts.app')

@section('title', 'Request Receipt')
@section('page_title', 'Request Receipt')
@section('page_subtitle', $requestDocument->ticket_number ?: 'Request #'.$requestDocument->id)

@section('content')
@php
    $ticketNumber = $requestDocument->ticket_number ?: 'Request #'.$requestDocument->id;
    $paymentMethod = match ($requestDocument->payment_method) {
        'gcash' => 'GCash',
        'bank_transfer' => 'Bank Transfer',
        'cash' => 'Cash',
        default => $requestDocument->payment_method ? ucfirst(str_replace('_', ' ', $requestDocument->payment_method)) : 'Not recorded',
    };
    $deliveryMethod = match ($requestDocument->delivery_method) {
        'pickup' => 'Pickup at Registrar’s Office',
        'delivery' => 'Delivery',
        default => $requestDocument->delivery_method ? ucfirst(str_replace('_', ' ', $requestDocument->delivery_method)) : 'Not recorded',
    };
    $requestStatus = match ($requestDocument->status) {
        'pending' => 'Pending',
        'processing' => 'Processing',
        'processed' => 'Processed',
        'ready_to_release' => 'Ready for Release',
        'completed' => 'Released',
        'rejected' => 'Rejected',
        default => ucfirst(str_replace('_', ' ', $requestDocument->status ?: 'unknown')),
    };
@endphp

<style>
    @page { size: A4 portrait; margin: 14mm; }
    @media print {
        html, body { background: #fff !important; color: #0f172a !important; }
        body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        #primary-sidebar, [data-mobile-sidebar-backdrop], main > header, main > footer,
        .receipt-actions, .screen-status, #fla-page-loader { display: none !important; }
        main { margin: 0 !important; min-height: auto !important; }
        .page-content { padding: 0 !important; }
        .receipt-page { width: 100% !important; max-width: none !important; box-shadow: none !important; border: 1px solid #cbd5e1 !important; }
        .receipt-section, .receipt-row { break-inside: avoid; }
        .print-status { display: inline !important; }
    }
</style>

<div class="request-a11y mx-auto min-w-0 max-w-4xl">
    <div class="receipt-actions mb-4 flex flex-col gap-3 sm:flex-row sm:justify-end">
        <a href="{{ auth()->user()->role === 'student' ? route('student.my-requests') : route('requests.index') }}" class="w-full rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50 sm:w-auto">
            Back to Requests
        </a>
        <button type="button" onclick="window.print()" class="w-full rounded-xl bg-[#000638] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#10175a] focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:w-auto">
            Print Receipt
        </button>
    </div>

    <article class="receipt-page min-w-0 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="receipt-title">
        <header class="receipt-section border-b border-slate-200 px-6 py-7 sm:px-10">
            <div class="flex items-center gap-4">
                <img src="{{ asset('images/fiat.png') }}" alt="{{ $schoolProfile['school'] }} seal" class="h-16 w-16 rounded-full object-contain">
                <div class="min-w-0">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Official Request Receipt</p>
                    <h1 id="receipt-title" class="mt-1 break-words text-xl font-black text-[#000638] sm:text-2xl">{{ $schoolProfile['school'] }}</h1>
                    @if($schoolProfile['district'] || $schoolProfile['division'] || $schoolProfile['region'])
                        <p class="mt-1 text-sm text-slate-600">{{ implode(' · ', array_filter([$schoolProfile['district'], $schoolProfile['division'], $schoolProfile['region']])) }}</p>
                    @endif
                    @if($schoolProfile['school_id'])
                        <p class="mt-1 text-xs font-semibold text-slate-500">School ID: {{ $schoolProfile['school_id'] }}</p>
                    @endif
                </div>
            </div>
        </header>

        <section class="receipt-section grid gap-px bg-slate-200 sm:grid-cols-2" aria-label="Receipt reference">
            <div class="bg-slate-50 px-6 py-5 sm:px-10">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Ticket Number</p>
                <p class="mt-1 break-words text-xl font-black text-[#000638]">{{ $ticketNumber }}</p>
            </div>
            <div class="bg-slate-50 px-6 py-5 sm:px-10">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Request Date</p>
                <p class="mt-1 text-base font-bold text-slate-900">{{ $requestDocument->created_at->format('F j, Y · g:i A') }}</p>
            </div>
        </section>

        <section class="receipt-section px-6 py-7 sm:px-10" aria-labelledby="student-details-heading">
            <h2 id="student-details-heading" class="text-sm font-black uppercase tracking-[0.14em] text-[#000638]">Student Details</h2>
            <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                <div class="receipt-row rounded-xl border border-slate-200 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Student Name</dt>
                    <dd class="mt-1 font-bold text-slate-900">{{ $requestDocument->student->name }}</dd>
                </div>
                <div class="receipt-row rounded-xl border border-slate-200 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Student Number</dt>
                    <dd class="mt-1 break-words font-bold text-slate-900">{{ $requestDocument->student_number }}</dd>
                </div>
            </dl>
        </section>

        <section class="receipt-section border-t border-slate-200 px-6 py-7 sm:px-10" aria-labelledby="request-details-heading">
            <h2 id="request-details-heading" class="text-sm font-black uppercase tracking-[0.14em] text-[#000638]">Request Details</h2>
            <dl class="mt-4 divide-y divide-slate-200 rounded-xl border border-slate-200">
                <div class="receipt-row grid gap-1 px-4 py-3 sm:grid-cols-[190px_1fr]"><dt class="text-sm font-medium text-slate-500">Requested Document</dt><dd class="font-semibold text-slate-900">{{ $requestDocument->document_type }}</dd></div>
                @if($requestDocument->school_year)
                    <div class="receipt-row grid gap-1 px-4 py-3 sm:grid-cols-[190px_1fr]"><dt class="text-sm font-medium text-slate-500">School Year</dt><dd class="font-semibold text-slate-900">{{ $requestDocument->school_year }}</dd></div>
                @endif
                <div class="receipt-row grid gap-1 px-4 py-3 sm:grid-cols-[190px_1fr]"><dt class="text-sm font-medium text-slate-500">Amount</dt><dd class="font-semibold text-slate-900">{{ $requestDocument->document_price !== null ? '₱'.number_format((float) $requestDocument->document_price, 2) : 'Not recorded' }}</dd></div>
                <div class="receipt-row grid gap-1 px-4 py-3 sm:grid-cols-[190px_1fr]"><dt class="text-sm font-medium text-slate-500">Payment Method</dt><dd class="font-semibold text-slate-900">{{ $paymentMethod }}</dd></div>
                <div class="receipt-row grid gap-1 px-4 py-3 sm:grid-cols-[190px_1fr]"><dt class="text-sm font-medium text-slate-500">Payment Status</dt><dd class="font-semibold text-slate-900">{{ $requestDocument->payment_confirmed ? 'Confirmed' : 'Pending Confirmation' }}</dd></div>
                <div class="receipt-row grid gap-1 px-4 py-3 sm:grid-cols-[190px_1fr]"><dt class="text-sm font-medium text-slate-500">Delivery Method</dt><dd class="font-semibold text-slate-900">{{ $deliveryMethod }}</dd></div>
                <div class="receipt-row grid gap-1 px-4 py-3 sm:grid-cols-[190px_1fr]">
                    <dt class="text-sm font-medium text-slate-500">Request Status</dt>
                    <dd>
                        <span class="screen-status"><x-request-status-badge :status="$requestDocument->status" /></span>
                        <span class="print-status hidden font-semibold text-slate-900">{{ $requestStatus }}</span>
                    </dd>
                </div>
            </dl>
        </section>

        <footer class="receipt-section border-t border-slate-200 bg-slate-50 px-6 py-5 text-sm leading-6 text-slate-600 sm:px-10">
            Keep this receipt and quote the ticket number when asking about this request. This receipt reflects the request’s current status at the time it is viewed.
        </footer>
    </article>
</div>
@endsection
