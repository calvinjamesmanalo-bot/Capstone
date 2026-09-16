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
                    <select id="student_number" name="student_number" required class="w-full rounded-lg border-slate-300">
                        <option value="">Select a student account</option>
                        @foreach($students as $student)
                            <option value="{{ $student->student_number }}" data-name="{{ $student->name }}" @selected($form['student_number'] === $student->student_number)>{{ $student->name }} — {{ $student->student_number }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Student name</label>
                    <input id="student_name" name="student_name" value="{{ $form['student_name'] }}" required readonly class="w-full rounded-lg border-slate-300 bg-slate-50 text-slate-600">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Grade level</label>
                    <select id="grade_level" name="grade_level" required class="w-full rounded-lg border-slate-300 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400">
                        <option value="">Select a student first</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">School year</label>
                    <select id="school_year" name="school_year" required class="w-full rounded-lg border-slate-300 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400">
                        <option value="">Select a grade level first</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Section <span class="font-normal text-slate-500">(optional)</span></label>
                    <select id="section" name="section" class="w-full rounded-lg border-slate-300 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400">
                        <option value="">Select a school year first</option>
                    </select>
                    <p id="academic-record-hint" class="mt-2 text-xs text-slate-500">Options come from the selected student's school-form records.</p>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Issue date</label>
                    <input type="date" name="issue_date" value="{{ $form['issue_date'] }}" required class="w-full rounded-lg border-slate-300">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Valid until <span class="font-normal text-slate-500">(optional)</span></label>
                    <input type="date" name="expires_at" value="{{ $form['expires_at'] }}" class="w-full rounded-lg border-slate-300">
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
                <button type="submit" class="w-full rounded-lg bg-[#062b63] px-6 py-3 text-sm font-semibold text-white hover:bg-[#041d45] sm:w-auto">Preview certificate</button>
            </div>
        </form>
    </div>
</div>

<script>
(() => {
    const academicRecordsByStudent = {{ \Illuminate\Support\Js::from($studentAcademicRecords) }};
    const initialValues = {{ \Illuminate\Support\Js::from([
        'grade_level' => $form['grade_level'],
        'school_year' => $form['school_year'],
        'section' => $form['section'],
    ]) }};
    const studentSelect = document.getElementById('student_number');
    const studentName = document.getElementById('student_name');
    const gradeLevel = document.getElementById('grade_level');
    const schoolYear = document.getElementById('school_year');
    const section = document.getElementById('section');
    const hint = document.getElementById('academic-record-hint');

    const unique = (values) => [...new Set(values.filter(Boolean))];

    const showUnavailable = (select, message) => {
        select.replaceChildren(new Option(message, ''));
        select.disabled = true;
    };

    const showOptions = (select, values, placeholder, preferred = '', optional = false) => {
        const firstOption = new Option(placeholder, '');
        firstOption.disabled = !optional;
        select.replaceChildren(firstOption);

        values.forEach((value) => select.add(new Option(value, value)));
        select.disabled = values.length === 0;

        if (values.includes(preferred)) {
            select.value = preferred;
        } else if (values.length === 1) {
            select.value = values[0];
        } else {
            select.value = '';
        }
    };

    const updateAcademicFields = (preferred = {}) => {
        const records = academicRecordsByStudent[studentSelect.value] ?? [];

        if (!studentSelect.value) {
            showUnavailable(gradeLevel, 'Select a student first');
            showUnavailable(schoolYear, 'Select a grade level first');
            showUnavailable(section, 'Select a school year first');
            hint.textContent = 'Select a student account to load available academic records.';
            return;
        }

        if (records.length === 0) {
            showUnavailable(gradeLevel, 'No grade levels available');
            showUnavailable(schoolYear, 'No school years available');
            showUnavailable(section, 'No sections available');
            hint.textContent = 'No imported enrollment records were found for this student.';
            return;
        }

        showOptions(
            gradeLevel,
            unique(records.map((record) => record.grade_level)),
            'Select grade level',
            preferred.grade_level ?? ''
        );

        const selectedGrade = gradeLevel.value;
        if (!selectedGrade) {
            showUnavailable(schoolYear, 'Select a grade level first');
            showUnavailable(section, 'Select a school year first');
            hint.textContent = `${records.length} enrollment record${records.length === 1 ? '' : 's'} available for this student.`;
            return;
        }

        const gradeRecords = records.filter((record) => record.grade_level === selectedGrade);
        showOptions(
            schoolYear,
            unique(gradeRecords.map((record) => record.school_year)),
            'Select school year',
            preferred.school_year ?? ''
        );

        const selectedYear = schoolYear.value;
        if (!selectedYear) {
            showUnavailable(section, 'Select a school year first');
            hint.textContent = `${gradeRecords.length} record${gradeRecords.length === 1 ? '' : 's'} available for ${selectedGrade}.`;
            return;
        }

        const matchingRecords = gradeRecords.filter((record) => record.school_year === selectedYear);
        const sections = unique(matchingRecords.map((record) => record.section));
        if (sections.length === 0) {
            showUnavailable(section, 'No section recorded');
        } else {
            showOptions(section, sections, 'No section', preferred.section ?? '', true);
        }
        hint.textContent = `Showing records for ${selectedGrade}, school year ${selectedYear}.`;
    };

    const updateStudent = (preferred = {}) => {
        const selectedOption = studentSelect.options[studentSelect.selectedIndex];
        studentName.value = selectedOption?.value ? selectedOption.dataset.name : '';
        updateAcademicFields(preferred);
    };

    studentSelect.addEventListener('change', () => updateStudent());
    gradeLevel.addEventListener('change', () => updateAcademicFields({ grade_level: gradeLevel.value }));
    schoolYear.addEventListener('change', () => updateAcademicFields({
        grade_level: gradeLevel.value,
        school_year: schoolYear.value,
    }));

    updateStudent(initialValues);
})();
</script>
@endsection
