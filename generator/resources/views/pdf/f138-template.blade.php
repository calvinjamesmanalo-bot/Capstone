<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Blank F138 Progress Report Card</title>
    <style>
        @page { size: a4 portrait; margin: 24px 28px; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #000; font-family: Arial, Helvetica, sans-serif; font-size: 9px; background-color: #fff; }
        .page { width: 100%; }
        .school-header { min-height: 82px; padding-top: 2px; text-align: center; }
        .brand { position: relative; width: 100%; min-height: 62px; margin: 0; padding-top: 5px; text-align: center; }
        .seal { position: absolute; top: 2; left: 135px; width: 64px; height: 64px; object-fit: contain; }
        .school-name { position: relative; left: 0px; width: 100%; margin: 0; text-align: center; font-family: DejaVu Serif, Georgia, 'Times New Roman', serif; font-size: 27px; font-weight: bold; letter-spacing: 0; line-height: 1.05; white-space: nowrap; }
        .location { position: relative; left: 0px; width: 100%; margin-top: 4px; text-align: center; font-size: 11px; font-weight: bold; }
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
        .grades .learning { width: 35%; text-align: left; padding-left: 7px; }
        .grades .period { width: 10%; }
        .grades .final { width: 15%; }
        .grades tbody td { height: 25px; }
        .grades tbody .conduct { font-size: 7px; }
        .grades .blank-row td { height: 23px; }
        .summary td { height: 22px; border: 1px solid #000; vertical-align: middle; }
        .summary .summary-label { padding-right: 9px; text-align: right; font-family: Georgia, 'Times New Roman', serif; font-size: 9px; font-style: italic; font-weight: bold; }
        .summary .summary-value { width: 15%; text-align: center; font-weight: bold; }
        .modality th, .modality td { height: 20px; border: 1px solid #000; text-align: center; }
        .modality th { width: 35%; font-family: Georgia, 'Times New Roman', serif; font-size: 9px; font-style: italic; }
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
        .muted-note { margin-top: 10px; text-align: center; color: #666; font-size: 7px; }
    </style>
</head>
<body>
@php
    $areas = ['Language', 'Reading and Literacy', 'Mathematics', 'Makabansa', 'Good Manners and Right Conduct'];
    $gradeMatrix = $gradeMatrix ?? [];
    $attendance = $attendance ?? [];
    $finalRatings = [];
    foreach ($areas as $area) {
        $periodGrades = array_values(array_filter($gradeMatrix[$area] ?? [], fn ($grade) => $grade !== null));
        $finalRatings[$area] = $periodGrades ? round(array_sum($periodGrades) / count($periodGrades)) : null;
    }
    $availableFinals = array_values(array_filter($finalRatings, fn ($grade) => $grade !== null));
    $generalAverage = $availableFinals ? round(array_sum($availableFinals) / count($availableFinals)) : null;
    $nextLevel = isset($enrollment) ? ((int) filter_var($enrollment->level, FILTER_SANITIZE_NUMBER_INT)) + 1 : null;
@endphp
<div class="page">
    <div class="school-header">
        <div class="brand">
            <img class="seal" src="{{ public_path('images/fla-seal.jpg') }}" alt="Fiat Lux Academe seal">
            <h1 class="school-name">FIAT LUX ACADEME</h1>
            <div class="location">Cavite</div>
        </div>
        <div class="report-title">PROGRESS REPORT CARD</div>
        <div class="level">Grade School</div>
    </div>

    <table class="student-info">
        <tr><td class="label">Student No.</td><td class="value">{{ $student->student_number ?? '' }}</td><td class="label">Academic Year</td><td class="value">{{ $enrollment->school_year ?? '' }}</td></tr>
        <tr><td class="label">Name</td><td class="value">{{ $student->name ?? '' }}</td><td class="label">LRN</td><td class="value">{{ $student->lrn ?? '' }}</td></tr>
        <tr><td class="label">Level &amp; Section</td><td colspan="3" class="value">{{ isset($enrollment) ? $enrollment->level.' - '.$enrollment->section : '' }}</td></tr>
    </table>

    <table class="grades">
        <thead>
        <tr><th rowspan="2" class="learning" style="text-align:center">LEARNING AREAS</th><th colspan="4">GRADING PERIODS</th><th rowspan="2" class="final">FINAL<br>RATING</th></tr>
        <tr><th class="period">1st</th><th class="period">2nd</th><th class="period">3rd</th><th class="period">4th</th></tr>
        </thead>
        <tbody>
        @foreach ($areas as $area)
            <tr>
                <td class="learning {{ $area === 'Good Manners and Right Conduct' ? 'conduct' : '' }}">{{ strtoupper($area) }}</td>
                @foreach (range(1, 4) as $period)<td>{{ isset($gradeMatrix[$area][$period]) ? number_format($gradeMatrix[$area][$period], 0) : '' }}</td>@endforeach
                <td>{{ $finalRatings[$area] !== null ? number_format($finalRatings[$area], 0) : '' }}</td>
            </tr>
        @endforeach
        @for ($row = 0; $row < 5; $row++)<tr class="blank-row"><td class="learning"></td><td></td><td></td><td></td><td></td><td></td></tr>@endfor
        </tbody>
    </table>
    <table class="summary"><tr><td class="summary-label">GENERAL AVERAGE</td><td class="summary-value">{{ $generalAverage !== null ? number_format($generalAverage, 0) : '' }}</td></tr></table>
    <table class="modality"><tr><th>LEARNING MODALITY</th><td>Blended</td><td>Blended</td><td>Blended</td><td>Blended</td><td style="width:15%"></td></tr></table>

    <div class="section-title">ATTENDANCE REPORT</div>
    <table class="attendance">
        @php($pdfMonths = ['Aug'=>'August','Sept'=>'September','Oct'=>'October','Nov'=>'November','Dec'=>'December','Jan'=>'January','Feb'=>'February','Mar'=>'March','Apr'=>'April','May'=>'May','Jun'=>'June'])
        <thead><tr><th class="row-label"></th>@foreach ($pdfMonths as $label=>$month)<th>{{ $label }}</th>@endforeach<th class="total">TOTAL</th></tr></thead>
        <tbody>
            <tr><td class="row-label">Days of School</td>@foreach ($pdfMonths as $month)<td>{{ $attendance[$month]['school_days'] ?? '' }}</td>@endforeach<td>{{ collect($pdfMonths)->sum(fn ($month) => $attendance[$month]['school_days'] ?? 0) ?: '' }}</td></tr>
            <tr><td class="row-label">Days Present</td>@foreach ($pdfMonths as $month)<td>{{ $attendance[$month]['days_present'] ?? '' }}</td>@endforeach<td>{{ collect($pdfMonths)->sum(fn ($month) => $attendance[$month]['days_present'] ?? 0) ?: '' }}</td></tr>
        </tbody>
    </table>

    <table class="remarks">
        <tr><th>REMARKS: {{ $generalAverage !== null ? ($generalAverage >= 75 ? 'PASSED' : 'RETAINED') : '' }}</th><th class="promotion">PROMOTED TO GRADE {{ $generalAverage !== null && $generalAverage >= 75 ? $nextLevel : '______' }}</th></tr>
        <tr class="writing"><td></td><td></td></tr>
        <tr class="signatures"><td>{{ isset($enrollment) && $enrollment->adviser_name ? $enrollment->adviser_name : '____________________________' }}</td><td>____________________________</td></tr>
        <tr class="roles"><td>Teacher-in-Charge</td><td>School Head</td></tr>
    </table>
    <div class="muted-note">Blank F138 template preview - values will be supplied from uploaded attendance and student summary workbooks.</div>
</div>
</body>
</html>
