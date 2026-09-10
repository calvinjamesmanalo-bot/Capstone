<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>F137 Draft Preview - {{ $student->name }}</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot'))) @vite(['resources/css/app.css', 'resources/js/app.js']) @endif
    <style>
        :root { color-scheme: light; --blue: #2563eb; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #eef2f7; color: #111827; font-family: Arial, Helvetica, sans-serif; }
        .preview-toolbar { position: sticky; top: 0; z-index: 30; display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 14px 24px; border-bottom: 1px solid #dbe2ea; background: rgba(255,255,255,.97); box-shadow: 0 2px 10px rgba(15,23,42,.08); }
        .preview-toolbar p { margin: 0 0 4px; color: #d97706; font-size: 11px; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; }
        .preview-toolbar h1 { margin: 0; font-size: 21px; }
        .toolbar-actions { display: flex; flex-wrap: wrap; gap: 9px; }
        .action { display: inline-flex; min-height: 40px; align-items: center; justify-content: center; border: 1px solid #cbd5e1; border-radius: 9px; padding: 9px 15px; background: #fff; color: #334155; font-size: 13px; font-weight: 700; text-decoration: none; cursor: pointer; }
        .action:hover { background: #f8fafc; }
        .action.primary { border-color: var(--blue); background: var(--blue); color: #fff; }
        .action.primary:hover { background: #1d4ed8; }
        .preview-note { max-width: 1120px; margin: 22px auto 0; padding: 0 18px; }
        .preview-note div { border: 1px solid #bfdbfe; border-radius: 10px; background: #eff6ff; padding: 12px 15px; color: #1e3a8a; font-size: 13px; line-height: 1.45; }
        .preview-note strong { color: #1e40af; }
        .sheet-wrap { overflow-x: auto; padding: 24px 18px 36px; }
        .sheet { position: relative; width: 210mm; min-height: 297mm; margin: 0 auto 24px; overflow: hidden; background: #fff; padding: 9mm; color: #000; box-shadow: 0 15px 38px rgba(15,23,42,.18); font-family: Arial, Helvetica, sans-serif; font-size: 7.5px; line-height: 1.12; }
        .draft-mark { position: absolute; top: 47%; left: 50%; z-index: 0; transform: translate(-50%,-50%) rotate(-28deg); color: rgba(185,28,28,.09); font-size: 72px; font-weight: 900; letter-spacing: .14em; white-space: nowrap; pointer-events: none; }
        .sheet-content { position: relative; z-index: 1; }
        .form-code, .page-number { position: absolute; top: 0; font-size: 8px; font-weight: 700; }
        .form-code { left: 0; } .page-number { right: 0; }
        .masthead { padding: 1mm 0 2mm; text-align: center; }
        .masthead .republic { font-family: Georgia, serif; font-size: 9px; }
        .masthead .department { margin-top: 1px; font-size: 9px; }
        .masthead h2 { margin: 5px 0 1px; font-size: 13px; }
        .masthead .former { font-family: Georgia, serif; font-size: 7px; font-style: italic; }
        .section-bar { border: 1px solid #000; background: #d1d5db; padding: 3px 5px; text-align: center; font-size: 8px; font-weight: 800; }
        .identity { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .identity td { height: 23px; padding: 4px 5px 2px; vertical-align: bottom; white-space: nowrap; }
        .write-line { display: inline-block; min-height: 11px; border-bottom: 1px solid #000; padding: 0 4px 1px; font-weight: 800; vertical-align: bottom; }
        .eligibility { border-right: 1px solid #000; border-left: 1px solid #000; padding: 4px 6px; line-height: 1.55; }
        .checkbox { display: inline-block; width: 8px; height: 8px; margin: 0 4px; border: 1px solid #000; vertical-align: middle; }
        .record-grid { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 6px; margin-top: 5px; }
        .record-card { min-width: 0; border: 1.2px solid #000; background: rgba(255,255,255,.88); }
        .record-meta { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .record-meta td { height: 16px; overflow: hidden; padding: 2px 3px; white-space: nowrap; font-size: 6.6px; }
        .grade-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .grade-table th, .grade-table td { height: 15px; border: 1px solid #000; padding: 1px 2px; text-align: center; font-size: 6.4px; }
        .grade-table th { font-weight: 800; }
        .grade-table .area { width: 43%; overflow: hidden; text-align: left; text-overflow: ellipsis; white-space: nowrap; }
        .grade-table thead .area { text-align: center; }
        .grade-table .quarter { width: 6%; }
        .grade-table .final { width: 11%; }
        .grade-table .remarks { width: 18%; }
        .grade-table .general th, .grade-table .general td { border-top-width: 2px; font-weight: 800; }
        .empty-record { display: grid; min-height: 270px; place-items: center; color: #64748b; font-size: 8px; font-style: italic; }
        .remedial-title { border-top: 1px solid #000; padding: 3px; text-align: center; font-size: 6px; font-weight: 800; }
        .certification { margin-top: 8px; border: 1.2px solid #000; padding: 11px 13px; font-size: 8px; line-height: 1.55; }
        .certification h3 { margin: 0 0 8px; text-align: center; font-size: 9px; }
        .signature-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 28px; margin-top: 27px; text-align: center; }
        .signature-line { border-top: 1px solid #000; padding-top: 3px; }
        .footer { position: absolute; right: 9mm; bottom: 5mm; font-size: 6px; }
        .limit-warning { margin: 6px 0; border: 1px solid #f59e0b; background: #fffbeb; padding: 6px; color: #92400e; font-size: 8px; }
        @media (max-width: 850px) {
            .preview-toolbar { align-items: flex-start; padding: 12px 14px; }
            .preview-toolbar h1 { font-size: 17px; }
            .toolbar-actions { justify-content: flex-end; }
            .preview-note { margin-top: 14px; }
            .sheet-wrap { padding: 16px 12px 28px; }
        }
        @media print {
            @page { size: A4 portrait; margin: 0; }
            body { background: #fff; }
            .preview-toolbar, .preview-note { display: none !important; }
            .sheet-wrap { overflow: visible; padding: 0; }
            .sheet { width: 210mm; min-height: 297mm; margin: 0; box-shadow: none; page-break-after: always; }
            .sheet:last-child { page-break-after: auto; }
        }
    </style>
</head>
<body>
@php
    $allRecords = collect($records);
    $frontRecords = $allRecords->take(4);
    $backRecords = $allRecords->slice(4, 4);
    $identifier = $student->student_number ?: $student->lrn;
    $downloadParameters = array_filter(['student' => $identifier, 'request_id' => $requestId]);
@endphp

<header class="preview-toolbar">
    <div>
        <p>Draft &middot; Not officially issued</p>
        <h1>F137 Draft Preview</h1>
    </div>
    <div class="toolbar-actions">
        <a class="action" href="{{ route('school-forms.home', ['student' => $identifier, 'form' => 'f137']) }}">Back</a>
        <button class="action" type="button" onclick="window.print()">Print preview</button>
        <a class="action primary" href="{{ route('school-forms.f137.download', $downloadParameters) }}">Download F137 Excel</a>
    </div>
</header>

<div class="preview-note">
    <div><strong>Live preview:</strong> subjects and quarterly grades below are read directly from the uploaded grade-sheet records. Use Print preview to inspect the page layout, or download the Excel file for the editable official template.</div>
</div>

<main class="sheet-wrap">
    <section class="sheet">
        <div class="draft-mark">DRAFT</div>
        <div class="sheet-content">
            <span class="form-code">SF10-ES</span><span class="page-number">Page 1 of 2</span>
            <div class="masthead">
                <div class="republic">Republic of the Philippines</div>
                <div class="department">Department of Education</div>
                <h2>Learner Permanent Record for Elementary School (SF10-ES)</h2>
                <div class="former">(Formerly Form 137)</div>
            </div>

            <div class="section-bar">LEARNER'S PERSONAL INFORMATION</div>
            <table class="identity">
                <tr>
                    <td style="width:29%">LAST NAME: <span class="write-line" style="width:65%">{{ $nameParts['last'] }}</span></td>
                    <td style="width:30%">FIRST NAME: <span class="write-line" style="width:65%">{{ $nameParts['first'] }}</span></td>
                    <td style="width:18%">NAME EXTN.: <span class="write-line" style="width:42%">{{ $nameParts['extension'] }}</span></td>
                    <td style="width:23%">MIDDLE NAME: <span class="write-line" style="width:54%">{{ $nameParts['middle'] }}</span></td>
                </tr>
                <tr>
                    <td colspan="2">Learner Reference Number (LRN): <span class="write-line" style="width:62%">{{ $student->lrn }}</span></td>
                    <td>Birthdate: <span class="write-line" style="width:58%"></span></td>
                    <td>Sex: <span class="write-line" style="width:78%"></span></td>
                </tr>
            </table>

            <div class="section-bar">ELIGIBILITY FOR ELEMENTARY SCHOOL ENROLMENT</div>
            <div class="eligibility">
                <em>Credential Presented for Grade 1:</em>
                <span class="checkbox"></span>Kinder Progress Report
                <span class="checkbox"></span>ECCD Checklist
                <span class="checkbox"></span>Kindergarten Certificate of Completion<br>
                Name of School: <span class="write-line" style="width:31%"></span>
                School ID: <span class="write-line" style="width:13%"></span>
                Address of School: <span class="write-line" style="width:28%"></span>
            </div>

            <div class="section-bar">SCHOLASTIC RECORD</div>
            <div class="record-grid">
                @foreach($frontRecords as $record)
                    @include('school-forms.partials.f137-record-preview', ['record' => $record, 'profile' => $profile])
                @endforeach
            </div>
            @if($frontRecords->isEmpty())
                <div class="empty-record">No uploaded school-year record is available.</div>
            @endif
        </div>
        <div class="footer">SFRT Revised 2017</div>
    </section>

    <section class="sheet">
        <div class="draft-mark">DRAFT</div>
        <div class="sheet-content">
            <span class="form-code">SF10-ES</span><span class="page-number">Page 2 of 2</span>
            <div class="section-bar" style="margin-top:4mm">SCHOLASTIC RECORD</div>
            @if($backRecords->isNotEmpty())
                <div class="record-grid">
                    @foreach($backRecords as $record)
                        @include('school-forms.partials.f137-record-preview', ['record' => $record, 'profile' => $profile])
                    @endforeach
                </div>
            @else
                <div class="empty-record">No additional school-year records.</div>
            @endif
            @if($allRecords->count() > 8)
                <div class="limit-warning">Only the first eight school-year records fit in the two-page F137 preview.</div>
            @endif

            <div class="section-bar">FOR TRANSFER OUT / ELEMENTARY SCHOOL COMPLETER ONLY</div>
            <div class="certification">
                <h3>CERTIFICATION</h3>
                I CERTIFY that this is a true record of
                <span class="write-line" style="width:33%">{{ $student->name }}</span>
                with LRN <span class="write-line" style="width:20%">{{ $student->lrn }}</span>
                and that the learner is eligible for admission to Grade <span class="write-line" style="width:8%"></span>.
                <div style="margin-top:8px">
                    School Name: <span class="write-line" style="width:25%">{{ $profile['school'] }}</span>
                    School ID: <span class="write-line" style="width:12%">{{ $profile['school_id'] }}</span>
                    Division: <span class="write-line" style="width:16%">{{ $profile['division'] }}</span>
                    Last School Year Attended: <span class="write-line" style="width:13%">{{ data_get($allRecords->last(), 'school_year') }}</span>
                </div>
                <div class="signature-grid">
                    <div class="signature-line">Date</div>
                    <div class="signature-line">Principal/School Head over Printed Name</div>
                    <div class="signature-line">Affix School Seal Here</div>
                </div>
            </div>
        </div>
        <div class="footer">SFRT Revised 2017</div>
    </section>
</main>
@include('partials.loading-overlay')
</body>
</html>
