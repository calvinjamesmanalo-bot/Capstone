<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Form 138 Progress Report Card</title>
    <style>
        @page { size: a4 portrait; margin: 24px 28px; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #000; font-family: Arial, Helvetica, sans-serif; font-size: 9px; background-color: #fff; }
        .page { width: 100%; }
        .school-header { min-height: 82px; padding-top: 2px; text-align: center; }
        .brand { position: relative; width: 100%; min-height: 62px; margin: 0; padding-top: 5px; text-align: center; }
        .seal { position: absolute; top: 2px; left: 135px; width: 64px; height: 64px; object-fit: contain; }
        .school-name { width: 100%; margin: 0; text-align: center; font-family: DejaVu Serif, Georgia, 'Times New Roman', serif; font-size: 27px; font-weight: bold; line-height: 1.05; white-space: nowrap; }
        .location { width: 100%; margin-top: 4px; text-align: center; font-size: 11px; font-weight: bold; }
        .report-title { margin-top: 7px; font-size: 18px; font-weight: bold; }
        .level { margin-top: 2px; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .student-info { margin-top: 15px; }
        .student-info td { height: 18px; padding: 2px 4px; border-bottom: 1px solid #b5b5b5; }
        .student-info .label { width: 86px; font-size: 10px; font-weight: bold; }
        .student-info .value { font-size: 10px; font-weight: bold; }
        .grades { margin-top: 8px; }
        .grades th, .grades td { border: 1px solid #000; padding: 3px; text-align: center; }
        .grades thead th { font-size: 10px; font-weight: bold; }
        .grades .learning { width: 42%; text-align: left; padding-left: 7px; }
        .grades .period { width: 12%; }
        .grades .final { width: 22%; }
        .grades tbody td { height: 25px; }
        .grades tbody .conduct { font-size: 7px; }
        .grades .blank-row td { height: 23px; }
        .summary td { height: 22px; border: 1px solid #000; vertical-align: middle; }
        .summary .summary-label { padding-right: 9px; text-align: right; font-family: Georgia, 'Times New Roman', serif; font-size: 9px; font-style: italic; font-weight: bold; }
        .summary .summary-value { width: 22%; text-align: center; font-weight: bold; }
        .modality th, .modality td { height: 20px; border: 1px solid #000; text-align: center; }
        .modality th { width: 42%; font-family: Georgia, 'Times New Roman', serif; font-size: 9px; font-style: italic; }
        .modality td { font-size: 9px; }
        .section-title { height: 23px; padding-top: 7px; text-align: center; font-family: Georgia, 'Times New Roman', serif; font-size: 10px; font-style: italic; font-weight: bold; }
        .attendance th, .attendance td { height: 22px; border: 1px solid #000; text-align: center; font-size: 8px; }
        .attendance .row-label { width: 22%; padding-left: 4px; text-align: left; }
        .attendance .total { width: 9%; }
        .remarks { margin-top: 36px; }
        .remarks th, .remarks td { border: 1px solid #000; }
        .remarks th { height: 22px; padding: 4px; text-align: left; font-family: Georgia, 'Times New Roman', serif; font-size: 8px; font-style: italic; }
        .remarks .promotion { text-align: center; font-family: Arial, Helvetica, sans-serif; font-style: normal; }
        .remarks .writing { height: 62px; }
        .signatures td { height: 34px; padding-top: 19px; text-align: center; font-weight: bold; }
        .roles td { height: 16px; text-align: center; font-family: Georgia, 'Times New Roman', serif; font-size: 8px; font-style: italic; }
        .draft-watermark { position: fixed; top: 43%; left: 13%; width: 74%; z-index: 99; transform: rotate(-28deg); border: 5px solid rgba(185, 28, 28, .16); padding: 14px; color: rgba(185, 28, 28, .16); font-size: 42px; font-weight: bold; letter-spacing: 5px; text-align: center; }
        .draft-watermark span { font-size: 13px; letter-spacing: 2px; }
    </style>
</head>
<body>
@if(($documentMode ?? 'draft') === 'draft')
    <div class="draft-watermark">DRAFT<br><span>NOT YET OFFICIALLY ISSUED</span></div>
@endif
@php
    $gradeMatrix = $gradeMatrix ?? [];
    $periodNumbers = \App\Support\AcademicPeriod::numbers($enrollment->school_year);
    $areas = array_keys(array_filter($gradeMatrix, fn ($grades) => collect($periodNumbers)->contains(fn ($period) => ($grades[$period] ?? null) !== null)));
    natcasesort($areas);
    $areas = array_values($areas);
    $attendance = $attendance ?? [];
    $finalRatings = [];
    foreach ($areas as $area) {
        $periodGrades = array_values(array_filter(
            array_map(fn ($period) => $gradeMatrix[$area][$period] ?? null, $periodNumbers),
            fn ($grade) => $grade !== null
        ));
        $finalRatings[$area] = $periodGrades ? round(array_sum($periodGrades) / count($periodGrades)) : null;
    }
    $availableFinals = array_values(array_filter($finalRatings, fn ($grade) => $grade !== null));
    $generalAverage = $availableFinals ? round(array_sum($availableFinals) / count($availableFinals)) : null;
    $levelNumber = (int) filter_var($enrollment->level, FILTER_SANITIZE_NUMBER_INT);
    $schoolDivision = $levelNumber >= 7 ? 'Junior High School' : 'Grade School';
    if ($generalAverage === null) {
        $result = '';
        $promotion = '';
    } elseif ($generalAverage < 75) {
        $result = 'RETAINED';
        $promotion = 'RETAINED IN '.strtoupper($enrollment->level);
    } elseif (strcasecmp($enrollment->level, 'Kinder') === 0) {
        $result = 'PROMOTED';
        $promotion = 'PROMOTED TO GRADE 1';
    } elseif ($levelNumber >= 10) {
        $result = 'COMPLETED';
        $promotion = 'COMPLETED JUNIOR HIGH SCHOOL';
    } else {
        $result = 'PROMOTED';
        $promotion = 'PROMOTED TO GRADE '.($levelNumber + 1);
    }
    $academicMonthOrder = ['June', 'July', 'August', 'September', 'October', 'November', 'December', 'January', 'February', 'March', 'April', 'May'];
    $attendanceMonths = array_values(array_filter(
        $academicMonthOrder,
        fn ($month) => isset($attendance[$month])
            && (($attendance[$month]['school_days'] ?? null) !== null || ($attendance[$month]['days_present'] ?? null) !== null)
    ));
    foreach (array_keys($attendance) as $month) {
        if (!in_array($month, $attendanceMonths, true)) {
            $attendanceMonths[] = $month;
        }
    }
    $monthLabels = ['January'=>'Jan','February'=>'Feb','March'=>'Mar','April'=>'Apr','May'=>'May','June'=>'Jun','July'=>'July','August'=>'Aug','September'=>'Sept','October'=>'Oct','November'=>'Nov','December'=>'Dec'];
@endphp
<div class="page">
    <div class="school-header">
        <div class="brand">
            <img class="seal" src="{{ public_path('images/fla-seal.jpg') }}" alt="Fiat Lux Academe seal">
            <h1 class="school-name">FIAT LUX ACADEME</h1>
            <div class="location">Cavite</div>
        </div>
        <div class="report-title">PROGRESS REPORT CARD</div>
        <div class="level">{{ $schoolDivision }}</div>
    </div>

    <table class="student-info">
        <tr><td class="label">Student No.</td><td class="value">{{ $student->student_number ?? '' }}</td><td class="label">Academic Year</td><td class="value">{{ $enrollment->school_year }}</td></tr>
        <tr><td class="label">Name</td><td class="value">{{ $student->name ?? '' }}</td><td class="label">LRN</td><td class="value">{{ $student->lrn ?? '' }}</td></tr>
        <tr><td class="label">Level &amp; Section</td><td colspan="3" class="value">{{ $enrollment->level }} - {{ $enrollment->section }}</td></tr>
    </table>

    <table class="grades">
        <thead>
        <tr><th rowspan="2" class="learning" style="text-align:center">LEARNING AREAS</th><th colspan="3">TERMS</th><th rowspan="2" class="final">FINAL<br>RATING</th></tr>
        <tr>@foreach ($periodNumbers as $period)<th class="period">TERM {{ $period }}</th>@endforeach</tr>
        </thead>
        <tbody>
        @foreach ($areas as $area)
            <tr>
                <td class="learning {{ $area === 'Good Manners and Right Conduct' ? 'conduct' : '' }}">{{ strtoupper($area) }}</td>
                @foreach ($periodNumbers as $period)<td>{{ isset($gradeMatrix[$area][$period]) ? number_format($gradeMatrix[$area][$period], 0) : '' }}</td>@endforeach
                <td>{{ $finalRatings[$area] !== null ? number_format($finalRatings[$area], 0) : '' }}</td>
            </tr>
        @endforeach
        @for ($row = count($areas); $row < 10; $row++)<tr class="blank-row"><td class="learning"></td><td></td><td></td><td></td><td></td></tr>@endfor
        </tbody>
    </table>
    <table class="summary"><tr><td class="summary-label">GENERAL AVERAGE</td><td class="summary-value">{{ $generalAverage !== null ? number_format($generalAverage, 0) : '' }}</td></tr></table>
    <table class="modality"><tr><th>LEARNING MODALITY</th>@foreach ($periodNumbers as $period)<td>Blended</td>@endforeach<td style="width:22%"></td></tr></table>

    <div class="section-title">ATTENDANCE REPORT</div>
    <table class="attendance">
        <thead><tr><th class="row-label"></th>@foreach ($attendanceMonths as $month)<th>{{ $monthLabels[$month] ?? $month }}</th>@endforeach<th class="total">TOTAL</th></tr></thead>
        <tbody>
            <tr><td class="row-label">Days of School</td>@foreach ($attendanceMonths as $month)<td>{{ $attendance[$month]['school_days'] ?? '' }}</td>@endforeach<td>{{ collect($attendanceMonths)->sum(fn ($month) => $attendance[$month]['school_days'] ?? 0) ?: '' }}</td></tr>
            <tr><td class="row-label">Days Present</td>@foreach ($attendanceMonths as $month)<td>{{ $attendance[$month]['days_present'] ?? '' }}</td>@endforeach<td>{{ collect($attendanceMonths)->sum(fn ($month) => $attendance[$month]['days_present'] ?? 0) ?: '' }}</td></tr>
        </tbody>
    </table>

    <table class="remarks">
        <tr><th>REMARKS: {{ $result }}</th><th class="promotion">{{ $promotion }}</th></tr>
        <tr class="writing"><td></td><td></td></tr>
        <tr class="signatures"><td>{{ $enrollment->adviser_name ?: '____________________________' }}</td><td>____________________________</td></tr>
        <tr class="roles"><td>Teacher-in-Charge</td><td>School Head</td></tr>
    </table>
    @if(($documentMode ?? 'draft') === 'official')
        @include('documents.partials.qr', [
            'qrDocumentType' => 'Form 138',
            'qrSubject' => $student->name,
            'qrRequestId' => $requestId ?? null,
            'qrHolderIdentifier' => $student->student_number ?? $student->lrn,
            'qrPdfWillBeSigned' => true,
            'qrFields' => [
                'school_year' => $enrollment->school_year,
                'level' => $enrollment->level,
                'section' => $enrollment->section,
                'grades' => $gradeMatrix,
                'attendance' => $attendance,
            ],
        ])
    @endif
</div>
</body>
</html>
