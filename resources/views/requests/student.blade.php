@extends('layouts.app')

@section('title', 'Request Document')
@section('page_title', 'Request a Document')
@section('page_subtitle', 'Complete three simple steps to send your request to the Registrar')

@section('content')
@php
    $selectedDocument = old('document_type', array_key_first($documentPrices));
    $selectedDelivery = old('delivery_method', 'pickup');
    $selectedPayment = old('payment_method', 'cash');
    $documentDescriptions = [
        'Form 137' => 'Permanent academic record',
        'Form 138' => 'Report card for a school year',
        'Certificate of Enrollment' => 'Proof of current enrollment',
        'Certificate of Completion' => 'Proof of program completion',
        'Certificate of Good Moral Character' => 'Certification of good conduct',
        'Certificate of Recognition' => 'Academic recognition record',
        'Diploma' => 'Official graduation credential',
    ];
    $initialStep = $errors->hasAny(['delivery_method', 'release_location', 'payment_method', 'transcript_receipt']) ? 2 : 1;
@endphp

<style>
    .request-choice input:checked + .request-choice-card {
        border-color: #000638;
        background: rgba(0, 6, 56, 0.035);
        box-shadow: 0 0 0 1px #000638;
    }
    .request-choice input:checked + .request-choice-card .request-choice-check {
        opacity: 1;
        transform: scale(1);
    }
    .request-choice input:focus-visible + .request-choice-card {
        outline: 3px solid rgba(255, 210, 45, 0.7);
        outline-offset: 2px;
    }
    .request-step-dot[data-state="active"] {
        background: #000638;
        border-color: #000638;
        color: #fff;
    }
    .request-step-dot[data-state="complete"] {
        background: #ffd22d;
        border-color: #ffd22d;
        color: #000638;
    }
    .request-step-label[data-state="active"],
    .request-step-label[data-state="complete"] { color: #000638; }
    html.dark .request-choice input:checked + .request-choice-card {
        border-color: #ffd22d !important;
        background: rgba(255, 210, 45, 0.08) !important;
        box-shadow: 0 0 0 1px #ffd22d;
    }
    html.dark .request-step-label[data-state="active"],
    html.dark .request-step-label[data-state="complete"] { color: #f8fafc; }
</style>

<div class="request-a11y mx-auto w-full min-w-0 max-w-[1440px]">
    @if($errors->any())
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-red-800" role="alert">
            <div class="flex items-start gap-3">
                <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86a2 2 0 001.73-3L13.73 4a2 2 0 00-3.46 0L3.34 16a2 2 0 001.73 3z" />
                </svg>
                <div>
                    <p class="text-sm font-bold">Please check the information below.</p>
                    <p class="mt-1 text-sm">Some required details are missing or invalid. Your previous selections have been kept.</p>
                </div>
            </div>
        </div>
    @endif

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 bg-slate-50 px-5 py-5 sm:px-7">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h3 class="text-xl font-bold text-[#000638]">New document request</h3>
                    <p class="mt-1 text-sm text-slate-500">Select a document, tell us how you want to receive it, then review your request.</p>
                </div>

                <nav class="flex w-full max-w-xl items-start" aria-label="Request progress">
                    @foreach(['Choose document', 'Delivery & payment', 'Review request'] as $index => $label)
                        <div class="flex min-w-0 flex-1 items-start">
                            <button type="button" class="group flex min-w-0 items-center gap-2.5 text-left" data-step-target="{{ $index + 1 }}" aria-label="Step {{ $index + 1 }}: {{ $label }}">
                                <span class="request-step-dot flex h-8 w-8 shrink-0 items-center justify-center rounded-full border-2 border-slate-300 bg-white text-sm font-bold text-slate-500 transition" data-step-dot="{{ $index + 1 }}">{{ $index + 1 }}</span>
                                <span class="request-step-label hidden text-xs font-semibold leading-tight text-slate-500 transition sm:block" data-step-label="{{ $index + 1 }}">{{ $label }}</span>
                            </button>
                            @if($index < 2)<span class="mx-2 mt-4 h-px min-w-3 flex-1 bg-slate-300" aria-hidden="true"></span>@endif
                        </div>
                    @endforeach
                </nav>
            </div>
        </div>

        <form id="document-request-form" action="{{ route('student.request.store') }}" method="POST" enctype="multipart/form-data" data-initial-step="{{ $initialStep }}" novalidate>
            @csrf

            <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_340px]">
                <div class="min-w-0 p-5 sm:p-7 lg:p-8">
                    @if(auth()->check() && auth()->user()->role === 'student')
                        <div class="mb-7 flex flex-col gap-3 rounded-xl border border-blue-200 bg-blue-50 p-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-3">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#000638] text-sm font-bold text-white" aria-hidden="true">{{ strtoupper(substr(auth()->user()->display_name, 0, 1)) }}</span>
                                <div>
                                    <p class="text-xs font-semibold text-blue-700">Requesting as</p>
                                    <p class="text-base font-bold text-[#000638]">{{ auth()->user()->display_name }}</p>
                                </div>
                            </div>
                            <span class="w-fit rounded-lg border border-blue-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700">Student ID: <strong class="text-[#000638]">{{ auth()->user()->student_number }}</strong></span>
                        </div>
                    @else
                        <div class="mb-7 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label for="student_number" class="mb-2 block text-sm font-semibold text-slate-700">Student number</label>
                                <input id="student_number" type="text" name="student_number" required value="{{ old('student_number', $studentNumber ?? '') }}" placeholder="e.g. 2023-0001" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-base font-semibold text-slate-800 focus:border-[#000638] focus:ring-2 focus:ring-[#000638]/20">
                                @error('student_number')<p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="name" class="mb-2 block text-sm font-semibold text-slate-700">Full name</label>
                                <input id="name" type="text" name="name" required value="{{ old('name', session('student_name') ?? '') }}" placeholder="e.g. Juan Dela Cruz" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-base font-semibold text-slate-800 focus:border-[#000638] focus:ring-2 focus:ring-[#000638]/20">
                                @error('name')<p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    @endif

                    <section data-step-panel="1" aria-labelledby="step-one-title">
                        <div class="mb-6">
                            <p class="text-sm font-semibold text-[#b28100]">Step 1 of 3</p>
                            <h4 id="step-one-title" class="mt-1 text-2xl font-bold text-[#000638]">Choose your document</h4>
                            <p class="mt-2 text-sm text-slate-500">The amount shown on each option is the official document fee.</p>
                        </div>

                        <fieldset>
                            <legend class="sr-only">Document type</legend>
                            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                @foreach($documentPrices as $document => $price)
                                    <label class="request-choice block cursor-pointer">
                                        <input class="sr-only" type="radio" name="document_type" value="{{ $document }}" data-price="{{ number_format($price, 2, '.', '') }}" required @checked($selectedDocument === $document)>
                                        <span class="request-choice-card flex min-h-24 items-center gap-4 rounded-xl border border-slate-200 bg-white p-4 transition hover:border-slate-400 hover:bg-slate-50">
                                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-[#000638]">
                                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 3h7l4 4v14H7a2 2 0 01-2-2V5a2 2 0 012-2zm7 0v5h5M9 13h6M9 17h4" /></svg>
                                            </span>
                                            <span class="min-w-0 flex-1">
                                                <span class="block text-base font-bold text-slate-800">{{ $document }}</span>
                                                <span class="mt-1 block text-sm text-slate-500">{{ $documentDescriptions[$document] ?? 'Official school document' }}</span>
                                            </span>
                                            <span class="self-start whitespace-nowrap text-sm font-bold text-[#000638]">&#8369;{{ number_format($price, 2) }}</span>
                                            <svg class="request-choice-check h-5 w-5 shrink-0 scale-75 text-[#000638] opacity-0 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m5 12 4 4L19 6" /></svg>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            @error('document_type')<p class="mt-3 text-sm font-medium text-red-600">{{ $message }}</p>@enderror
                        </fieldset>

                        <div id="school_year_field" class="mt-6 hidden rounded-xl border border-slate-200 bg-slate-50 p-5">
                            <label for="school_year" class="block text-base font-bold text-slate-800">Which school year do you need?</label>
                            <p class="mt-1 text-sm text-slate-500">The Records Officer will use this when preparing your Form 138.</p>
                            <div class="relative mt-4 max-w-md">
                                <select name="school_year" id="school_year" class="w-full appearance-none rounded-xl border border-slate-300 bg-white px-4 py-3 pr-11 text-base font-semibold text-slate-800 focus:border-[#000638] focus:ring-2 focus:ring-[#000638]/20">
                                    <option value="">Choose school year</option>
                                    @foreach (config('academics.school_years', []) as $year)<option value="{{ $year }}" @selected(old('school_year') === $year)>{{ $year }}</option>@endforeach
                                </select>
                                <svg class="pointer-events-none absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6" /></svg>
                            </div>
                            @error('school_year')<p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div id="school_level_field" class="mt-6 hidden rounded-xl border border-slate-200 bg-slate-50 p-5">
                            <fieldset>
                                <legend class="text-base font-bold text-slate-800">Which school level's Form 137 do you need?</legend>
                                <p class="mt-1 text-sm text-slate-500">Select the level whose permanent record you are requesting.</p>
                                <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-3">
                                    @foreach(['elementary' => 'Kinder and Elementary', 'jhs' => 'Junior High School (JHS)', 'shs' => 'Senior High School (SHS)'] as $value => $label)
                                        <label class="request-choice block cursor-pointer">
                                            <input class="sr-only" type="radio" name="school_level" value="{{ $value }}" @checked(in_array(old('school_level'), $value === 'elementary' ? ['kinder', 'elementary'] : [$value], true))>
                                            <span class="request-choice-card flex min-h-16 items-center justify-between gap-3 rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-500">
                                                <span>{{ $label }}</span>
                                                <svg class="request-choice-check h-5 w-5 shrink-0 scale-75 text-[#000638] opacity-0 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m5 12 4 4L19 6" /></svg>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                            @error('school_level')<p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div class="mt-8 flex justify-end">
                            <button type="button" data-next-step="2" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#000638] px-6 py-3.5 text-base font-bold text-white transition hover:bg-[#10175a] focus:outline-none focus:ring-4 focus:ring-[#ffd22d]/50 sm:w-auto">Continue to delivery <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7" /></svg></button>
                        </div>
                    </section>

                    <section data-step-panel="2" class="hidden" aria-labelledby="step-two-title">
                        <div class="mb-6">
                            <p class="text-sm font-semibold text-[#b28100]">Step 2 of 3</p>
                            <h4 id="step-two-title" class="mt-1 text-2xl font-bold text-[#000638]">Delivery and payment</h4>
                            <p class="mt-2 text-sm text-slate-500">Choose your preferred options and attach your Accounting clearance receipt.</p>
                        </div>

                        <div class="space-y-7">
                            <fieldset>
                                <legend class="text-base font-bold text-slate-800">How would you like to receive the document?</legend>
                                <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2">
                                    @foreach(['pickup' => ["Pickup at Registrar's Office", 'No delivery fee'], 'delivery' => ['Deliver to my address', 'Additional fee may apply']] as $value => $details)
                                        <label class="request-choice block cursor-pointer">
                                            <input class="sr-only" type="radio" name="delivery_method" value="{{ $value }}" required @checked($selectedDelivery === $value)>
                                            <span class="request-choice-card flex min-h-20 items-center gap-3 rounded-xl border border-slate-300 bg-white p-4 transition hover:border-slate-500">
                                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-[#000638]">
                                                    @if($value === 'pickup')<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 21h18M5 21V8l7-5 7 5v13M9 12h6M9 16h6" /></svg>@else<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7h11v10H3zM14 10h4l3 3v4h-7M7 20a2 2 0 100-4 2 2 0 000 4zm10 0a2 2 0 100-4 2 2 0 000 4z" /></svg>@endif
                                                </span>
                                                <span class="min-w-0 flex-1"><span class="block text-sm font-bold text-slate-800">{{ $details[0] }}</span><span class="mt-1 block text-sm text-slate-500">{{ $details[1] }}</span></span>
                                                <svg class="request-choice-check h-5 w-5 shrink-0 scale-75 text-[#000638] opacity-0 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m5 12 4 4L19 6" /></svg>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                                @error('delivery_method')<p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>@enderror
                            </fieldset>

                            <div id="release_location_field" class="hidden">
                                <label for="release_location" class="mb-2 block text-sm font-semibold text-slate-700">Complete delivery address</label>
                                <textarea id="release_location" name="release_location" rows="3" placeholder="House/Unit, Street, Barangay, City, Province" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-base text-slate-800 placeholder:text-slate-400 focus:border-[#000638] focus:ring-2 focus:ring-[#000638]/20">{{ old('release_location') }}</textarea>
                                <p class="mt-2 text-sm text-slate-500">The Registrar will confirm the delivery fee before processing.</p>
                                @error('release_location')<p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <fieldset>
                                <legend class="text-base font-bold text-slate-800">How would you like to pay?</legend>
                                <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-3">
                                    @foreach(['cash' => ['Cash', 'Pay at Registrar'], 'gcash' => ['GCash', 'Mobile payment'], 'bank_transfer' => ['Bank transfer', 'Online transfer']] as $value => $details)
                                        <label class="request-choice block cursor-pointer">
                                            <input class="sr-only" type="radio" name="payment_method" value="{{ $value }}" required @checked($selectedPayment === $value)>
                                            <span class="request-choice-card flex min-h-20 items-center justify-between gap-3 rounded-xl border border-slate-300 bg-white p-4 transition hover:border-slate-500">
                                                <span><span class="block text-sm font-bold text-slate-800">{{ $details[0] }}</span><span class="mt-1 block text-sm text-slate-500">{{ $details[1] }}</span></span>
                                                <svg class="request-choice-check h-5 w-5 shrink-0 scale-75 text-[#000638] opacity-0 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m5 12 4 4L19 6" /></svg>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                                @error('payment_method')<p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>@enderror
                            </fieldset>

                            <div>
                                <label for="transcript_receipt" class="block text-base font-bold text-slate-800">Accounting clearance receipt <span class="text-red-600">*</span></label>
                                <p class="mt-1 text-sm text-slate-500">Upload the transcript clearance receipt issued by Accounting.</p>
                                <label for="transcript_receipt" class="mt-3 flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 px-5 py-7 text-center transition hover:border-[#000638] hover:bg-blue-50">
                                    <svg class="h-8 w-8 text-[#000638]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 16a4 4 0 01-.9-7.9A5 5 0 0115.9 7H17a4 4 0 010 8h-1m-4-5v9m0-9-3 3m3-3 3 3" /></svg>
                                    <span id="receipt_file_name" class="mt-3 text-sm font-bold text-[#000638]">Choose a JPG, PNG, or PDF file</span>
                                    <span class="mt-1 text-sm text-slate-500">Maximum file size: 5 MB</span>
                                </label>
                                <input type="file" name="transcript_receipt" id="transcript_receipt" accept="image/jpeg,image/png,.pdf" required class="sr-only">
                                @error('transcript_receipt')<p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="mt-8 flex flex-col-reverse gap-3 sm:flex-row sm:justify-between">
                            <button type="button" data-previous-step="1" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-5 py-3.5 text-base font-bold text-slate-700 transition hover:bg-slate-50"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 19-7-7 7-7" /></svg>Back</button>
                            <button type="button" data-next-step="3" class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#000638] px-6 py-3.5 text-base font-bold text-white transition hover:bg-[#10175a] focus:outline-none focus:ring-4 focus:ring-[#ffd22d]/50">Review request <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7" /></svg></button>
                        </div>
                    </section>

                    <section data-step-panel="3" class="hidden" aria-labelledby="step-three-title">
                        <div class="mb-6">
                            <p class="text-sm font-semibold text-[#b28100]">Step 3 of 3</p>
                            <h4 id="step-three-title" class="mt-1 text-2xl font-bold text-[#000638]">Review your request</h4>
                            <p class="mt-2 text-sm text-slate-500">Make sure the details are correct before submitting.</p>
                        </div>

                        <div class="overflow-hidden rounded-xl border border-slate-200">
                            <dl class="divide-y divide-slate-200">
                                <div class="grid grid-cols-1 gap-1 bg-white px-5 py-4 sm:grid-cols-[180px_1fr]"><dt class="text-sm font-medium text-slate-500">Document</dt><dd id="review_document" class="text-base font-bold text-slate-800"></dd></div>
                                <div id="review_school_detail_row" class="grid grid-cols-1 gap-1 bg-white px-5 py-4 sm:grid-cols-[180px_1fr]"><dt id="review_school_detail_label" class="text-sm font-medium text-slate-500"></dt><dd id="review_school_detail" class="text-base font-semibold text-slate-800"></dd></div>
                                <div class="grid grid-cols-1 gap-1 bg-white px-5 py-4 sm:grid-cols-[180px_1fr]"><dt class="text-sm font-medium text-slate-500">Delivery</dt><dd id="review_delivery" class="text-base font-semibold text-slate-800"></dd></div>
                                <div id="review_address_row" class="hidden grid-cols-1 gap-1 bg-white px-5 py-4 sm:grid-cols-[180px_1fr]"><dt class="text-sm font-medium text-slate-500">Delivery address</dt><dd id="review_address" class="break-words text-base font-semibold text-slate-800"></dd></div>
                                <div class="grid grid-cols-1 gap-1 bg-white px-5 py-4 sm:grid-cols-[180px_1fr]"><dt class="text-sm font-medium text-slate-500">Payment</dt><dd id="review_payment" class="text-base font-semibold text-slate-800"></dd></div>
                                <div class="grid grid-cols-1 gap-1 bg-white px-5 py-4 sm:grid-cols-[180px_1fr]"><dt class="text-sm font-medium text-slate-500">Receipt</dt><dd id="review_receipt" class="break-all text-base font-semibold text-slate-800"></dd></div>
                            </dl>
                            <div class="flex items-center justify-between gap-4 bg-[#000638] px-5 py-4 text-white"><span class="text-sm font-semibold">Document fee</span><strong id="review_total" class="text-xl text-[#ffd22d]"></strong></div>
                        </div>

                        <div class="mt-6 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4">
                            <svg class="mt-0.5 h-5 w-5 shrink-0 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <div><p class="text-sm font-bold text-amber-900">Before you submit</p><p class="mt-1 text-sm leading-relaxed text-amber-800">You cannot submit another active request for the same document. Contact the Registrar's Office if a previous request needs to be completed or cleared.</p></div>
                        </div>

                        <div class="mt-8 flex flex-col-reverse gap-3 sm:flex-row sm:justify-between">
                            <button type="button" data-previous-step="2" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-5 py-3.5 text-base font-bold text-slate-700 transition hover:bg-slate-50"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 19-7-7 7-7" /></svg>Edit details</button>
                            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#000638] px-7 py-3.5 text-base font-bold text-white shadow-sm transition hover:bg-[#10175a] focus:outline-none focus:ring-4 focus:ring-[#ffd22d]/50"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4 4L19 6" /></svg>Submit request</button>
                        </div>
                    </section>
                </div>

                <aside class="border-t border-slate-200 bg-slate-50 p-5 xl:border-l xl:border-t-0 xl:p-6" aria-label="Request summary">
                    <div class="xl:sticky xl:top-28">
                        <div class="flex items-center justify-between gap-3"><h4 class="text-base font-bold text-[#000638]">Request summary</h4><span class="rounded-full bg-[#ffd22d] px-2.5 py-1 text-xs font-bold text-[#000638]">Draft</span></div>
                        <div class="mt-5 rounded-xl border border-slate-200 bg-white p-4">
                            <p class="text-xs font-semibold text-slate-500">Selected document</p>
                            <p id="summary_document" class="mt-1 text-base font-bold text-slate-800"></p>
                            <p id="summary_document_detail" class="mt-1 text-sm text-slate-500"></p>
                            <div class="my-4 border-t border-slate-200"></div>
                            <dl class="space-y-3 text-sm">
                                <div class="flex items-start justify-between gap-4"><dt class="text-slate-500">Delivery</dt><dd id="summary_delivery" class="text-right font-semibold text-slate-800"></dd></div>
                                <div class="flex items-start justify-between gap-4"><dt class="text-slate-500">Payment</dt><dd id="summary_payment" class="text-right font-semibold text-slate-800"></dd></div>
                                <div class="flex items-center justify-between gap-4 border-t border-slate-200 pt-3"><dt class="font-semibold text-slate-700">Document fee</dt><dd id="summary_total" class="text-lg font-bold text-[#000638]"></dd></div>
                            </dl>
                        </div>
                        <div class="mt-5 rounded-xl border border-blue-200 bg-blue-50 p-4">
                            <div class="flex gap-3">
                                <svg class="mt-0.5 h-5 w-5 shrink-0 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <div><p class="text-sm font-bold text-blue-900">What happens next?</p><p class="mt-1 text-sm leading-relaxed text-blue-800">The Registrar will review your request. You can track its status anytime from My Requests.</p></div>
                            </div>
                        </div>
                        <a href="{{ route('student.my-requests') }}" class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-100">View my existing requests <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7" /></svg></a>
                    </div>
                </aside>
            </div>
        </form>
    </div>
</div>

<script>
(() => {
    const form = document.getElementById('document-request-form');
    if (!form) return;

    const panels = Array.from(form.querySelectorAll('[data-step-panel]'));
    const stepDots = Array.from(document.querySelectorAll('[data-step-dot]'));
    const stepLabels = Array.from(document.querySelectorAll('[data-step-label]'));
    const stepTargets = Array.from(document.querySelectorAll('[data-step-target]'));
    const documentInputs = Array.from(form.querySelectorAll('input[name="document_type"]'));
    const deliveryInputs = Array.from(form.querySelectorAll('input[name="delivery_method"]'));
    const paymentInputs = Array.from(form.querySelectorAll('input[name="payment_method"]'));
    const schoolYearField = document.getElementById('school_year_field');
    const schoolYearSelect = document.getElementById('school_year');
    const schoolLevelField = document.getElementById('school_level_field');
    const schoolLevelInputs = Array.from(form.querySelectorAll('input[name="school_level"]'));
    const addressField = document.getElementById('release_location_field');
    const addressInput = document.getElementById('release_location');
    const receiptInput = document.getElementById('transcript_receipt');
    const receiptFileName = document.getElementById('receipt_file_name');
    let currentStep = Number(form.dataset.initialStep || 1);
    let highestVisited = currentStep;

    const checkedValue = (name) => form.querySelector('input[name="' + name + '"]:checked')?.value || '';
    const selectedDocumentInput = () => form.querySelector('input[name="document_type"]:checked');
    const selectedDocumentName = () => selectedDocumentInput()?.value || '';
    const selectedPrice = () => Number(selectedDocumentInput()?.dataset.price || 0);
    const money = (amount) => '\u20B1' + amount.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const readableDelivery = () => checkedValue('delivery_method') === 'delivery' ? 'Delivery to my address' : "Pickup at Registrar's Office";
    const readablePayment = () => ({ cash: 'Cash at Registrar', gcash: 'GCash', bank_transfer: 'Bank transfer' }[checkedValue('payment_method')] || 'Not selected');
    const documentDescriptions = @json($documentDescriptions);

    function setText(id, value) {
        const element = document.getElementById(id);
        if (element) element.textContent = value;
    }

    function updateConditionalFields() {
        const documentName = selectedDocumentName();
        const needsYear = documentName === 'Form 138';
        const needsLevel = documentName === 'Form 137';
        const needsAddress = checkedValue('delivery_method') === 'delivery';
        schoolYearField.classList.toggle('hidden', !needsYear);
        schoolYearSelect.required = needsYear;
        schoolLevelField.classList.toggle('hidden', !needsLevel);
        schoolLevelInputs.forEach((input) => input.required = needsLevel);
        addressField.classList.toggle('hidden', !needsAddress);
        addressInput.required = needsAddress;
    }

    function updateSummary() {
        const documentName = selectedDocumentName() || 'Not selected';
        const price = selectedPrice();
        const schoolLevel = form.querySelector('input[name="school_level"]:checked')?.nextElementSibling?.querySelector('span')?.textContent.trim() || '';
        const schoolYear = schoolYearSelect.value;
        const schoolDetail = documentName === 'Form 137' ? schoolLevel : (documentName === 'Form 138' ? schoolYear : '');
        setText('summary_document', documentName);
        setText('summary_document_detail', documentDescriptions[documentName] || 'Official school document');
        setText('summary_delivery', readableDelivery());
        setText('summary_payment', readablePayment());
        setText('summary_total', money(price));
        setText('review_document', documentName);
        setText('review_delivery', readableDelivery());
        setText('review_payment', readablePayment());
        setText('review_receipt', receiptInput.files[0]?.name || 'No file selected');
        setText('review_total', money(price));

        const detailRow = document.getElementById('review_school_detail_row');
        detailRow.classList.toggle('hidden', !schoolDetail);
        detailRow.classList.toggle('grid', Boolean(schoolDetail));
        setText('review_school_detail_label', documentName === 'Form 137' ? 'School level' : 'School year');
        setText('review_school_detail', schoolDetail);

        const addressRow = document.getElementById('review_address_row');
        const hasAddress = checkedValue('delivery_method') === 'delivery';
        addressRow.classList.toggle('hidden', !hasAddress);
        addressRow.classList.toggle('grid', hasAddress);
        setText('review_address', addressInput.value || 'Not provided');
    }

    function showStep(step, scroll = true) {
        currentStep = step;
        highestVisited = Math.max(highestVisited, step);
        panels.forEach((panel) => panel.classList.toggle('hidden', Number(panel.dataset.stepPanel) !== step));
        stepDots.forEach((dot) => {
            const number = Number(dot.dataset.stepDot);
            const state = number < step ? 'complete' : (number === step ? 'active' : 'upcoming');
            dot.dataset.state = state;
            dot.textContent = number < step ? '\u2713' : String(number);
        });
        stepLabels.forEach((label) => {
            const number = Number(label.dataset.stepLabel);
            label.dataset.state = number < step ? 'complete' : (number === step ? 'active' : 'upcoming');
        });
        stepTargets.forEach((button) => {
            const number = Number(button.dataset.stepTarget);
            button.disabled = number > highestVisited;
            button.classList.toggle('cursor-not-allowed', button.disabled);
            button.classList.toggle('opacity-60', button.disabled);
            if (number === step) button.setAttribute('aria-current', 'step'); else button.removeAttribute('aria-current');
        });
        updateSummary();
        if (scroll) form.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function validateStep(step) {
        const panel = form.querySelector('[data-step-panel="' + step + '"]');
        for (const field of Array.from(panel.querySelectorAll('input, select, textarea'))) {
            if (!field.checkValidity()) {
                field.reportValidity();
                field.focus({ preventScroll: false });
                return false;
            }
        }
        return true;
    }

    documentInputs.forEach((input) => input.addEventListener('change', () => { updateConditionalFields(); updateSummary(); }));
    deliveryInputs.forEach((input) => input.addEventListener('change', () => { updateConditionalFields(); updateSummary(); }));
    paymentInputs.forEach((input) => input.addEventListener('change', updateSummary));
    schoolLevelInputs.forEach((input) => input.addEventListener('change', updateSummary));
    schoolYearSelect.addEventListener('change', updateSummary);
    addressInput.addEventListener('input', updateSummary);
    receiptInput.addEventListener('change', () => {
        receiptFileName.textContent = receiptInput.files[0]?.name || 'Choose a JPG, PNG, or PDF file';
        updateSummary();
    });
    form.querySelectorAll('[data-next-step]').forEach((button) => button.addEventListener('click', () => { if (validateStep(currentStep)) showStep(Number(button.dataset.nextStep)); }));
    form.querySelectorAll('[data-previous-step]').forEach((button) => button.addEventListener('click', () => showStep(Number(button.dataset.previousStep))));
    stepTargets.forEach((button) => button.addEventListener('click', () => { const target = Number(button.dataset.stepTarget); if (target <= highestVisited) showStep(target); }));
    form.addEventListener('submit', (event) => {
        updateConditionalFields();
        if (!form.checkValidity()) {
            event.preventDefault();
            const invalid = form.querySelector(':invalid');
            const panel = invalid?.closest('[data-step-panel]');
            if (panel) showStep(Number(panel.dataset.stepPanel), false);
            invalid?.reportValidity();
        }
    });

    updateConditionalFields();
    updateSummary();
    showStep(currentStep, false);
})();
</script>
@endsection
