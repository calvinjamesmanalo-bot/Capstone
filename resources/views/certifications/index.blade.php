@extends('layouts.app')

@section('title', 'Certification Maker')
@section('page_title', 'Certification Maker')
@section('page_subtitle', 'Create official school certificates')

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
        <div class="p-6 md:p-8 border-l-4 border-[#d59b11] bg-slate-50">
            <div class="flex items-center gap-4">
                <img src="{{ asset('images/fiat.png') }}" alt="Fiat Lux Academe logo" class="w-14 h-14 object-contain">
                <div>
                    <h2 class="text-2xl font-semibold text-[#062b63]">Create a certificate</h2>
                    <p class="mt-1 text-sm text-slate-600">Choose a type, enter the student details, then review the document before printing.</p>
                </div>
            </div>
        </div>

        <form action="{{ route('certifications.preview') }}" method="POST" class="p-6 md:p-8 space-y-6">
            @csrf
            @if(($form['request_id'] ?? '') !== '')
                <input type="hidden" name="request_id" value="{{ $form['request_id'] }}">
            @endif
            @if($errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                    @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Certificate type</label>
                    <select name="certificate_type" required class="w-full rounded-lg border-slate-300 focus:border-[#062b63] focus:ring-[#062b63]">
                        <option value="">Select a certificate type</option>
                        @foreach($certificateTypes as $key => $type)
                            <option value="{{ $key }}" @selected($form['certificate_type'] === $key)>{{ $type['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Student</label>
                    <select id="student_number" name="student_number" class="w-full rounded-lg border-slate-300" onchange="const option=this.options[this.selectedIndex]; if(option.value) document.getElementById('student_name').value=option.dataset.name;">
                        <option value="">Enter a name manually</option>
                        @foreach($students as $student)
                            <option value="{{ $student->student_number }}" data-name="{{ $student->name }}" @selected($form['student_number'] === $student->student_number)>{{ $student->name }} — {{ $student->student_number }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Student name</label>
                    <input id="student_name" name="student_name" value="{{ $form['student_name'] }}" required class="w-full rounded-lg border-slate-300">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Grade level</label>
                    <input name="grade_level" value="{{ $form['grade_level'] }}" placeholder="e.g. Grade 10" required class="w-full rounded-lg border-slate-300">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Section <span class="font-normal text-slate-500">(optional)</span></label>
                    <input name="section" value="{{ $form['section'] }}" placeholder="e.g. Rizal" class="w-full rounded-lg border-slate-300">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">School year</label>
                    <input name="school_year" value="{{ $form['school_year'] }}" placeholder="2026-2027" required class="w-full rounded-lg border-slate-300">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Issue date</label>
                    <input type="date" name="issue_date" value="{{ $form['issue_date'] }}" required class="w-full rounded-lg border-slate-300">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Purpose <span class="font-normal text-slate-500">(optional)</span></label>
                    <input name="purpose" value="{{ $form['purpose'] }}" placeholder="e.g. scholarship application" class="w-full rounded-lg border-slate-300">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Recognition title <span class="font-normal text-slate-500">(required only for recognition)</span></label>
                    <input name="recognition" value="{{ $form['recognition'] }}" placeholder="e.g. Outstanding Student" class="w-full rounded-lg border-slate-300">
                </div>
            </div>

            <div class="flex justify-end border-t border-slate-200 pt-6">
                <button type="submit" class="rounded-lg bg-[#062b63] px-6 py-3 text-sm font-semibold text-white hover:bg-[#041d45]">Preview certificate</button>
            </div>
        </form>
    </div>
</div>
@endsection
