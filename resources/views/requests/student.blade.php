@extends('layouts.app')

@section('title', 'Request Document')
@section('page_title', 'Student Request Portal')
@section('page_subtitle', 'Submit a new document request to the registrar')

@section('content')
<div class="w-full">
    <!-- Request Form -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-200 bg-slate-50">
            <h3 class="font-bold text-[#000638] text-xl">New document request</h3>
            <p class="text-sm text-slate-500 mt-1">Complete the required information below.</p>
        </div>
        
        <form action="{{ route('student.request.store') }}" method="POST" enctype="multipart/form-data" class="p-6 grid grid-cols-1 xl:grid-cols-2 gap-5">
            @csrf
            
            @if(auth()->check() && auth()->user()->role === 'student')
                <div class="xl:col-span-2 p-5 bg-[#ffd22d]/10 rounded-xl border border-[#ffd22d]/50 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex min-h-14 items-center justify-between gap-4 rounded-lg border border-[#ffd22d]/40 bg-white/70 px-4 py-3">
                        <span class="text-xs font-semibold text-slate-500">Student ID</span>
                        <span class="text-base font-bold text-[#000638]">{{ auth()->user()->student_number }}</span>
                    </div>
                    <div class="flex min-h-14 items-center justify-between gap-4 rounded-lg border border-[#ffd22d]/40 bg-white/70 px-4 py-3">
                        <span class="text-xs font-semibold text-slate-500">Full name</span>
                        <span class="text-base font-bold text-[#000638] text-right">{{ auth()->user()->display_name }}</span>
                    </div>
                </div>
            @else
                <div class="space-y-3">
                    <label class="text-xs font-black text-slate-500 uppercase tracking-[0.2em] ml-1">Student Number</label>
                    <input type="text" name="student_number" required value="{{ $studentNumber ?? '' }}" placeholder="e.g. 2023-0001"
                        class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-indigo-500 transition-all font-bold text-slate-800 placeholder:text-slate-400 text-base">
                </div>

                <div class="space-y-3">
                    <label class="text-xs font-black text-slate-500 uppercase tracking-[0.2em] ml-1">Full Name</label>
                    <input type="text" name="name" required value="{{ session('student_name') ?? '' }}" placeholder="e.g. Juan Dela Cruz"
                        class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-indigo-500 transition-all font-bold text-slate-800 placeholder:text-slate-400 text-base">
                </div>
            @endif

            <div class="xl:col-span-2 overflow-hidden rounded-xl border border-slate-200">
                <div class="flex items-center justify-between gap-4 bg-[#000638] px-5 py-3 text-white">
                    <div>
                        <h4 class="text-sm font-bold">Document price list</h4>
                        <p class="mt-0.5 text-xs text-slate-300">Official fees set by the administrator</p>
                    </div>
                    <span class="rounded-lg bg-[#ffd22d] px-3 py-1.5 text-xs font-bold text-[#000638]">₱100–₱150</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 text-xs text-slate-500">
                            <tr>
                                <th class="px-5 py-2.5 font-semibold">Document</th>
                                <th class="px-5 py-2.5 text-right font-semibold">Price</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white text-sm">
                            @foreach($documentPrices as $document => $price)
                                <tr>
                                    <td class="px-5 py-2.5 text-slate-700">{{ $document }}</td>
                                    <td class="px-5 py-2.5 text-right font-bold text-[#000638]">₱{{ number_format($price, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="space-y-3" id="school_year_field" style="display: none;">
                <label for="school_year" class="text-xs font-black text-slate-500 uppercase tracking-[0.2em] ml-1">
                    School Year for Form 138
                </label>
                <select name="school_year" id="school_year"
                    class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-indigo-500 transition-all font-bold text-slate-800 appearance-none text-base">
                    <option value="">Choose school year</option>
                    @foreach (config('academics.school_years', []) as $year)
                        <option value="{{ $year }}" @selected(old('school_year') === $year)>{{ $year }}</option>
                    @endforeach
                </select>
                <p class="text-[11px] text-slate-500 font-medium">The records officer will use this school year when preparing your Form 138.</p>
            </div>

            <div class="space-y-3" id="school_level_field" style="display: none;">
                <label for="school_level" class="text-xs font-black text-slate-500 uppercase tracking-[0.2em] ml-1">
                    School Level for Form 137
                </label>
                <select name="school_level" id="school_level"
                    class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-indigo-500 transition-all font-bold text-slate-800 appearance-none text-base">
                    <option value="">Choose school level</option>
                    <option value="elementary" @selected(in_array(old('school_level'), ['kinder', 'elementary'], true))>Kinder and Elementary</option>
                    <option value="jhs" @selected(old('school_level') === 'jhs')>Junior High School (JHS)</option>
                    <option value="shs" @selected(old('school_level') === 'shs')>Senior High School (SHS)</option>
                </select>
                <p class="text-[11px] text-slate-500 font-medium">Select the school level whose permanent record you are requesting.</p>
            </div>

            <div class="space-y-3">
                <div class="flex items-center justify-between gap-3">
                    <label class="text-xs font-black text-slate-500 uppercase tracking-[0.2em] ml-1">Document Type</label>
                    <span id="selected_document_price" class="text-sm font-bold text-[#000638]"></span>
                </div>
                <select name="document_type" id="document_type" required
                    class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-indigo-500 transition-all font-bold text-slate-800 appearance-none text-base">
                    @foreach($documentPrices as $document => $price)
                        <option value="{{ $document }}" data-price="{{ number_format($price, 2, '.', '') }}" @selected(old('document_type') === $document)>{{ $document }}</option>
                    @endforeach
                </select>
            </div>

            <div class="space-y-3">
                <label class="text-xs font-black text-slate-500 uppercase tracking-[0.2em] ml-1">Delivery Method</label>
                <select name="delivery_method" id="delivery_method" required
                    class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-indigo-500 transition-all font-bold text-slate-800 appearance-none text-base">
                    <option value="pickup">Pickup at Registrar's Office</option>
                    <option value="delivery">Delivery (Additional Fee May Apply)</option>
                </select>
            </div>

            <div class="space-y-3 xl:col-span-2" id="release_location_field" style="display: none;">
                <label class="text-xs font-black text-slate-500 uppercase tracking-[0.2em] ml-1">Delivery Address</label>
                <input type="text" name="release_location" placeholder="Enter complete delivery address"
                    class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-indigo-500 transition-all font-bold text-slate-800 placeholder:text-slate-400 text-base">
            </div>

            <div class="space-y-3">
                <label class="text-xs font-black text-slate-500 uppercase tracking-[0.2em] ml-1">Payment Method</label>
                <select name="payment_method" required id="payment_method_select"
                    class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-indigo-500 transition-all font-bold text-slate-800 appearance-none text-base">
                    <option value="cash">Cash (at Registrar)</option>
                    <option value="gcash">GCash</option>
                    <option value="bank_transfer">Bank Transfer</option>
                </select>
            </div>

            <div class="space-y-3 xl:col-span-2">
                <label class="text-xs font-black text-slate-500 uppercase tracking-[0.2em] ml-1" for="transcript_receipt">
                    Upload Transcript Receipt from Accounting <span class="text-red-500">*</span>
                </label>
                <input type="file" name="transcript_receipt" id="transcript_receipt" accept="image/jpeg,image/png,.pdf" required
                    class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-indigo-500 transition-all font-bold text-slate-700 text-sm">
                <p class="text-[11px] text-red-600 font-bold">
                    Required: attach the transcript clearance receipt issued by Accounting before submitting your request.
                </p>
            </div>

            <div class="pt-2 xl:col-span-2 flex justify-end">
                <button type="submit" class="w-full sm:w-auto px-8 py-3 bg-[#000638] text-sm font-semibold rounded-lg shadow-sm hover:bg-[#10175a] transition-colors flex items-center justify-center gap-3 text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                    Submit Request
                </button>
            </div>
        </form>

        <div class="px-6 pb-6">
            <div class="p-4 bg-amber-50 rounded-xl border border-amber-200 flex items-start gap-4">
                <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-amber-600 shadow-sm shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <h4 class="text-xs font-black text-amber-800 uppercase tracking-tight">Important Note</h4>
                    <p class="text-[11px] text-amber-700 font-medium mt-1 leading-relaxed">
                        If you have an active request, you cannot submit another one for the same document.
                        If you need to request again, <strong>you need to go to the Registrar's Office to complete or clear your previous request.</strong>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Shortcut to My Requests -->

</div>

<script>
document.getElementById('delivery_method').addEventListener('change', function() {
    const locationField = document.getElementById('release_location_field');
    if (this.value === 'delivery') {
        locationField.style.display = 'block';
    } else {
        locationField.style.display = 'none';
    }
});

const documentTypeSelect = document.getElementById('document_type');
const selectedDocumentPrice = document.getElementById('selected_document_price');

function updateSelectedDocumentPrice() {
    const option = documentTypeSelect.options[documentTypeSelect.selectedIndex];
    selectedDocumentPrice.textContent = 'Fee: ₱' + Number(option.dataset.price).toFixed(2);
}

const schoolYearField = document.getElementById('school_year_field');
const schoolYearSelect = document.getElementById('school_year');
const schoolLevelField = document.getElementById('school_level_field');
const schoolLevelSelect = document.getElementById('school_level');

function updateDocumentFields() {
    updateSelectedDocumentPrice();
    const needsSchoolYear = documentTypeSelect.value === 'Form 138';
    const needsSchoolLevel = documentTypeSelect.value === 'Form 137';
    schoolYearField.style.display = needsSchoolYear ? 'block' : 'none';
    schoolYearSelect.required = needsSchoolYear;
    if (!needsSchoolYear) schoolYearSelect.value = '';
    schoolLevelField.style.display = needsSchoolLevel ? 'block' : 'none';
    schoolLevelSelect.required = needsSchoolLevel;
    if (!needsSchoolLevel) schoolLevelSelect.value = '';
}

updateDocumentFields();
documentTypeSelect.addEventListener('change', updateDocumentFields);

</script>
@endsection
