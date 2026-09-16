<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>F137 Draft Preview - {{ $student->name }}</title>
    <style>
        @page { size: A4 portrait; margin: 6mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #000; font-family: Arial, Helvetica, sans-serif; font-size: 6px; line-height: 1.08; }
        .page { position: relative; width: 100%; page-break-after: always; }
        .page:last-child { page-break-after: auto; }
        .draft-mark { position: absolute; top: 46%; left: 18%; z-index: -1; transform: rotate(-28deg); color: rgba(185,28,28,.09); font-size: 66px; font-weight: bold; letter-spacing: 8px; }
        .form-code { float: left; font-size: 7px; font-weight: bold; }
        .page-number { float: right; font-size: 7px; font-weight: bold; }
        .masthead { clear: both; padding: 1mm 0 1.5mm; text-align: center; }
        .masthead .republic { font-family: DejaVu Serif, serif; font-size: 8px; }
        .masthead .department { font-size: 8px; }
        .masthead h1 { margin: 3px 0 1px; font-size: 11px; }
        .masthead .former { font-family: DejaVu Serif, serif; font-size: 6px; font-style: italic; }
        .section-bar { clear: both; border: 1px solid #000; background: #d1d5db; padding: 2px 4px; text-align: center; font-size: 6.5px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .identity td { height: 15px; padding: 2px 3px 1px; vertical-align: bottom; white-space: nowrap; }
        .write-line { display: inline-block; min-height: 8px; border-bottom: 1px solid #000; padding: 0 2px 1px; font-weight: bold; vertical-align: bottom; }
        .eligibility { min-height: 25px; border-right: 1px solid #000; border-left: 1px solid #000; padding: 3px 5px; line-height: 1.45; }
        .checkbox { display: inline-block; width: 6px; height: 6px; margin: 0 3px; border: 1px solid #000; vertical-align: middle; }
        .record-layout { border-collapse: separate; border-spacing: 3px; }
        .record-layout td { padding: 0; vertical-align: top; }
        .record-card { width: 100%; border: 1px solid #000; background: #fff; page-break-inside: avoid; }
        .record-meta td { height: 11px; overflow: hidden; padding: 1px 2px; white-space: nowrap; font-size: 5px; }
        .grade-table th, .grade-table td { height: 10px; border: 1px solid #000; padding: 1px; text-align: center; font-size: 5px; }
        .grade-table th { font-weight: bold; }
        .grade-table .area { width: 43%; overflow: hidden; text-align: left; white-space: nowrap; }
        .grade-table thead .area { text-align: center; }
        .grade-table .quarter { width: 6%; }
        .grade-table .final { width: 11%; }
        .grade-table .remarks { width: 18%; }
        .grade-table .general th, .grade-table .general td { border-top-width: 1.5px; font-weight: bold; }
        .remedial-title { border-top: 1px solid #000; padding: 2px; text-align: center; font-size: 4.8px; font-weight: bold; }
        .empty-record { min-height: 80px; border: 1px solid #000; padding-top: 35px; text-align: center; color: #666; font-style: italic; }
        .certification { margin-top: 5px; border: 1px solid #000; padding: 7px 9px; font-size: 6px; line-height: 1.45; page-break-inside: avoid; }
        .certification h2 { margin: 0 0 6px; text-align: center; font-size: 7px; }
        .signature-grid { margin-top: 18px; border-collapse: separate; border-spacing: 15px 0; }
        .signature-grid td { border-top: 1px solid #000; padding-top: 2px; text-align: center; font-size: 5px; }
        .footer { margin-top: 3px; text-align: right; font-size: 5px; }
    </style>
</head>
<body>
@php
    $allRecords = collect($records);
    $isJhs = $schoolLevel === 'jhs';
    $frontCapacity = $isJhs ? 2 : 4;
    $backCapacity = $isJhs ? 3 : 4;
    $columns = $isJhs ? 1 : 2;
    $formCode = $isJhs ? 'SF10-JHS' : 'SF10-ES';
    $schoolStage = $isJhs ? 'Junior High School' : 'Elementary School';
    $frontRecords = $allRecords->take($frontCapacity);
    $backRecords = $allRecords->slice($frontCapacity, $backCapacity);
@endphp

<section class="page">
    <div class="draft-mark">DRAFT</div>
    <span class="form-code">{{ $formCode }}</span><span class="page-number">Page 1 of 2</span>
    <div class="masthead">
        <div class="republic">Republic of the Philippines</div>
        <div class="department">Department of Education</div>
        <h1>Learner Permanent Record for {{ $schoolStage }} ({{ $formCode }})</h1>
        <div class="former">(Formerly Form 137)</div>
    </div>

    <div class="section-bar">LEARNER'S PERSONAL INFORMATION</div>
    <table class="identity">
        <tr>
            <td style="width:29%">LAST NAME: <span class="write-line" style="width:62%">{{ $nameParts['last'] }}</span></td>
            <td style="width:30%">FIRST NAME: <span class="write-line" style="width:62%">{{ $nameParts['first'] }}</span></td>
            <td style="width:18%">NAME EXTN.: <span class="write-line" style="width:38%">{{ $nameParts['extension'] }}</span></td>
            <td style="width:23%">MIDDLE NAME: <span class="write-line" style="width:50%">{{ $nameParts['middle'] }}</span></td>
        </tr>
        <tr>
            <td colspan="2">Learner Reference Number (LRN): <span class="write-line" style="width:60%">{{ $student->lrn }}</span></td>
            <td>Birthdate: <span class="write-line" style="width:55%"></span></td>
            <td>Sex: <span class="write-line" style="width:75%"></span></td>
        </tr>
    </table>

    <div class="section-bar">ELIGIBILITY FOR {{ strtoupper($schoolStage) }} ENROLMENT</div>
    <div class="eligibility">
        @if($isJhs)
            <em>Elementary School Completer:</em> <span class="checkbox"></span>
            General Average: <span class="write-line" style="width:12%"></span>
            Citation: <span class="write-line" style="width:28%"></span><br>
            Name of Elementary School: <span class="write-line" style="width:27%"></span>
            School ID: <span class="write-line" style="width:12%"></span>
            Address: <span class="write-line" style="width:22%"></span>
        @else
            <em>Credential Presented for Grade 1:</em>
            <span class="checkbox"></span>Kinder Progress Report
            <span class="checkbox"></span>ECCD Checklist
            <span class="checkbox"></span>Kindergarten Certificate of Completion<br>
            Name of School: <span class="write-line" style="width:30%"></span>
            School ID: <span class="write-line" style="width:12%"></span>
            Address of School: <span class="write-line" style="width:27%"></span>
        @endif
    </div>

    <div class="section-bar">SCHOLASTIC RECORD</div>
    @include('school-forms.pdf.partials.f137-record-grid', [
        'pageRecords' => $frontRecords,
        'columns' => $columns,
        'profile' => $profile,
    ])
    <div class="footer">{{ $isJhs ? 'Revised 2025' : 'SFRT Revised 2017' }}</div>
</section>

<section class="page">
    <div class="draft-mark">DRAFT</div>
    <span class="form-code">{{ $formCode }}</span><span class="page-number">Page 2 of 2</span>
    <div class="section-bar" style="margin-top:12px">SCHOLASTIC RECORD</div>
    @if($backRecords->isNotEmpty())
        @include('school-forms.pdf.partials.f137-record-grid', [
            'pageRecords' => $backRecords,
            'columns' => $columns,
            'profile' => $profile,
        ])
    @else
        <div class="empty-record">No additional school-year records.</div>
    @endif

    <div class="section-bar">FOR TRANSFER OUT / {{ strtoupper($schoolStage) }} COMPLETER ONLY</div>
    <div class="certification">
        <h2>CERTIFICATION</h2>
        I CERTIFY that this is a true record of
        <span class="write-line" style="width:32%">{{ $student->name }}</span>
        with LRN <span class="write-line" style="width:18%">{{ $student->lrn }}</span>
        and that the learner is eligible for admission to Grade <span class="write-line" style="width:7%"></span>.
        <div style="margin-top:6px">
            School Name: <span class="write-line" style="width:23%">{{ $profile['school'] }}</span>
            School ID: <span class="write-line" style="width:10%">{{ $profile['school_id'] }}</span>
            Division: <span class="write-line" style="width:14%">{{ $profile['division'] }}</span>
            Last School Year Attended: <span class="write-line" style="width:11%">{{ data_get($allRecords->last(), 'school_year') }}</span>
        </div>
        <table class="signature-grid">
            <tr><td>Date</td><td>Principal/School Head over Printed Name</td><td>Affix School Seal Here</td></tr>
        </table>
    </div>
    <div class="footer">{{ $isJhs ? 'Revised 2025' : 'SFRT Revised 2017' }}</div>
</section>
</body>
</html>
