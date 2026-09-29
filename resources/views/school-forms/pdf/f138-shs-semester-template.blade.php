<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Senior High School Form 138</title>
    <style>
        @page { size: a4 portrait; margin: 18px 28px; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #000; font: 8px Arial, Helvetica, sans-serif; }
        .header { position: relative; min-height: 72px; text-align: center; }
        .seal { position: absolute; top: 0; left: 132px; width: 58px; height: 58px; object-fit: contain; }
        h1 { margin: 0; font: bold 25px DejaVu Serif, Georgia, serif; }
        .location { margin-top: 3px; font-size: 10px; font-weight: bold; }
        .title { margin-top: 8px; font-size: 16px; font-weight: bold; }
        .division { margin-top: 2px; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .student { margin: 7px 0 6px; }
        .student td { height: 16px; padding: 1px 4px; border-bottom: 1px solid #999; font-size: 9px; }
        .student .label { width: 80px; font-weight: bold; }
        .grades th, .grades td { height: 18px; border: 1px solid #000; padding: 2px 4px; text-align: center; }
        .grades .area { width: 43%; text-align: left; }
        .grades thead .area { text-align: center; }
        .grades .rating { width: 18%; }
        .grades .remarks-col { width: 14%; }
        .grades .section-row th { height: 17px; background: #eee; text-align: left; font-style: italic; }
        .grades .general-label { text-align: right; font-weight: bold; }
        .section-title { padding: 5px 0 3px; text-align: center; font: italic bold 9px Georgia, serif; }
        .attendance th, .attendance td { height: 17px; border: 1px solid #000; text-align: center; }
        .attendance .row-label { width: 25%; padding-left: 4px; text-align: left; }
        .weighted td { height: 19px; border: 1px solid #000; text-align: center; font-weight: bold; }
        .weighted .label { width: 43%; text-align: right; padding-right: 7px; }
        .remarks { margin-top: 8px; }
        .remarks th, .remarks td { border: 1px solid #000; }
        .remarks th { height: 19px; padding: 3px; text-align: left; }
        .remarks .promotion { text-align: center; }
        .remarks .writing { height: 28px; }
        .signatures td { height: 27px; padding-top: 13px; text-align: center; font-weight: bold; }
        .roles td { height: 14px; text-align: center; font: italic 8px Georgia, serif; }
        .draft-watermark { position: fixed; top: 43%; left: 13%; width: 74%; z-index: 99; transform: rotate(-28deg); border: 5px solid rgba(185,28,28,.16); padding: 14px; color: rgba(185,28,28,.16); font-size: 42px; font-weight: bold; letter-spacing: 5px; text-align: center; }
        .draft-watermark span { font-size: 13px; letter-spacing: 2px; }
    </style>
</head>
<body>
@if(($documentMode ?? 'draft') === 'draft')
    <div class="draft-watermark">DRAFT<br><span>NOT YET OFFICIALLY ISSUED</span></div>
@endif
@php
    $gradeMatrix = $gradeMatrix ?? [];
    $attendance = $attendance ?? [];
    $semesterPeriods = [1 => [1, 2], 2 => [3, 4]];
    $semesterAreas = [];
    $semesterAverages = [];
    foreach ($semesterPeriods as $semester => $periods) {
        $semesterAreas[$semester] = array_keys(array_filter($gradeMatrix, fn ($grades) => collect($periods)->contains(fn ($period) => ($grades[$period] ?? null) !== null)));
        natcasesort($semesterAreas[$semester]);
        $semesterAreas[$semester] = array_values($semesterAreas[$semester]);
        $finals = [];
        foreach ($semesterAreas[$semester] as $area) {
            $values = array_values(array_filter(array_map(fn ($period) => $gradeMatrix[$area][$period] ?? null, $periods), fn ($grade) => $grade !== null));
            $finals[] = $values ? round(array_sum($values) / count($values)) : null;
        }
        $finals = array_values(array_filter($finals, fn ($grade) => $grade !== null));
        $semesterAverages[$semester] = $finals ? round(array_sum($finals) / count($finals)) : null;
    }
    $availableSemesterAverages = array_values(array_filter($semesterAverages, fn ($grade) => $grade !== null));
    $weightedAverage = $availableSemesterAverages ? round(array_sum($availableSemesterAverages) / count($availableSemesterAverages)) : null;
    $levelNumber = (int) filter_var($enrollment->level, FILTER_SANITIZE_NUMBER_INT);
    $result = $weightedAverage === null ? '' : ($weightedAverage >= 75 ? ($levelNumber >= 12 ? 'COMPLETED' : 'PROMOTED') : 'RETAINED');
    $promotion = $weightedAverage === null ? '' : ($weightedAverage < 75 ? 'RETAINED IN '.strtoupper($enrollment->level) : ($levelNumber >= 12 ? 'COMPLETED SENIOR HIGH SCHOOL' : 'PROMOTED TO GRADE TWELVE'));
    $monthLabels = ['January'=>'Jan','February'=>'Feb','March'=>'Mar','April'=>'Apr','May'=>'May','June'=>'Jun','July'=>'July','August'=>'Aug','September'=>'Sept','October'=>'Oct','November'=>'Nov','December'=>'Dec'];
    $semesterMonths = [1 => ['June','July','August','September','October','November'], 2 => ['December','January','February','March','April','May']];
@endphp
<div class="header">
    <img class="seal" src="{{ \App\Support\SystemContent::logoPath() }}" alt="Fiat Lux Academe seal">
    <h1> {{ mb_strtoupper(\App\Support\SystemContent::schoolName()) }}</h1><div class="location">{{ \App\Support\SystemContent::get('school_address', 'Cavite') }}</div>
    <div class="title">PROGRESS REPORT CARD</div><div class="division">Senior High School</div>
</div>
<table class="student">
    <tr><td class="label">Student No.</td><td>{{ $student->student_number ?? '' }}</td><td class="label">Academic Year</td><td>{{ $enrollment->school_year }}</td></tr>
    <tr><td class="label">Name</td><td>{{ $student->name ?? '' }}</td><td class="label">LRN</td><td>{{ $student->lrn ?? '' }}</td></tr>
    <tr><td class="label">Level &amp; Section</td><td colspan="3">{{ $enrollment->level }} - {{ $enrollment->section }}</td></tr>
</table>
@foreach ($semesterPeriods as $semester => $periods)
    <table class="grades">
        <thead><tr><th rowspan="2" class="area">LEARNING AREAS</th><th colspan="2">GRADING PERIOD</th><th rowspan="2" class="rating">{{ $semester === 1 ? '1ST' : '2ND' }} SEMESTER<br>FINAL RATING</th><th rowspan="2" class="remarks-col">REMARKS</th></tr>
        <tr><th>{{ $periods[0] }}{{ $periods[0] === 1 ? 'st' : 'rd' }}</th><th>{{ $periods[1] }}{{ $periods[1] === 2 ? 'nd' : 'th' }}</th></tr></thead>
        <tbody>
        @foreach ($semesterAreas[$semester] as $area)
            @php
                $values = array_values(array_filter(array_map(fn ($period) => $gradeMatrix[$area][$period] ?? null, $periods), fn ($grade) => $grade !== null));
                $final = $values ? round(array_sum($values) / count($values)) : null;
            @endphp
            <tr><td class="area">{{ strtoupper($area) }}</td>@foreach($periods as $period)<td>{{ isset($gradeMatrix[$area][$period]) ? number_format($gradeMatrix[$area][$period], 0) : '' }}</td>@endforeach<td>{{ $final !== null ? number_format($final, 0) : '' }}</td><td>{{ $final === null ? '' : ($final >= 75 ? 'PASSED' : 'FAILED') }}</td></tr>
        @endforeach
        @for($row = count($semesterAreas[$semester]); $row < 7; $row++)<tr><td class="area"></td><td></td><td></td><td></td><td></td></tr>@endfor
        <tr><td colspan="3" class="general-label">GENERAL AVERAGE</td><td>{{ $semesterAverages[$semester] !== null ? number_format($semesterAverages[$semester], 0) : '' }}</td><td></td></tr>
        </tbody>
    </table>
    <div class="section-title">ATTENDANCE REPORT</div>
    <table class="attendance"><tr><th class="row-label"></th>@foreach($semesterMonths[$semester] as $month)<th>{{ $monthLabels[$month] }}</th>@endforeach<th>TOTAL</th></tr>
        <tr><td class="row-label">Days of School</td>@foreach($semesterMonths[$semester] as $month)<td>{{ $attendance[$month]['school_days'] ?? '' }}</td>@endforeach<td>{{ collect($semesterMonths[$semester])->sum(fn ($month) => $attendance[$month]['school_days'] ?? 0) ?: '' }}</td></tr>
        <tr><td class="row-label">Days Present</td>@foreach($semesterMonths[$semester] as $month)<td>{{ $attendance[$month]['days_present'] ?? '' }}</td>@endforeach<td>{{ collect($semesterMonths[$semester])->sum(fn ($month) => $attendance[$month]['days_present'] ?? 0) ?: '' }}</td></tr>
    </table>
    @if($semester === 1)<div style="height:6px"></div>@endif
@endforeach
<table class="weighted"><tr><td class="label">GENERAL WEIGHTED AVERAGE</td><td>{{ $semesterAverages[1] !== null ? number_format($semesterAverages[1], 0) : '' }}</td><td>{{ $semesterAverages[2] !== null ? number_format($semesterAverages[2], 0) : '' }}</td><td>{{ $weightedAverage !== null ? number_format($weightedAverage, 0) : '' }}</td></tr></table>
<table class="remarks"><tr><th>REMARKS: {{ $result }}</th><th class="promotion">{{ $promotion }}</th></tr><tr class="writing"><td></td><td></td></tr><tr class="signatures"><td>{{ $enrollment->adviser_name ?: '____________________________' }}</td><td>____________________________</td></tr><tr class="roles"><td>Adviser</td><td>Vice Principal</td></tr></table>
@if(in_array(($documentMode ?? 'draft'), ['generated', 'official'], true))
    @include('documents.partials.qr', ['qrDocumentType'=>'Form 138','qrSubject'=>$student->name,'qrRequestId'=>$requestId ?? null,'qrHolderIdentifier'=>$student->student_number ?? $student->lrn,'qrPdfWillBeSigned'=>true,'qrFields'=>['school_year'=>$enrollment->school_year,'level'=>$enrollment->level,'section'=>$enrollment->section,'grades'=>$gradeMatrix,'attendance'=>$attendance]])
@endif
</body>
</html>
