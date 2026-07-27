<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>SF10-ES</title>
<style>
@page { size: A4 portrait; margin: 4mm; }
* { box-sizing: border-box; }
html, body { margin: 0; padding: 0; font-family: Arial, DejaVu Sans, sans-serif; color: #000; font-size: 8.8px; line-height: 1.05; text-align: center; }
.page { position: relative; width: 198mm; height: 285mm; margin-left: auto; margin-right: auto; overflow: hidden; text-align: left; }
.page-break { page-break-after: always; }
.code { position: absolute; left: 0; top: 0; font-weight: bold; font-size: 8.5px; }
.page-no { position: absolute; right: 1mm; top: 0; font-size: 8.5px; }
.masthead { height: 20mm; position: relative; text-align: center; padding-top: .5mm; }
.seal { position: absolute; left: 6mm; top: 0; width: 16mm; height: 15mm; object-fit: contain; }
.deped { position: absolute; right: 5mm; top: 2mm; width: 27mm; height: 13mm; object-fit: contain; }
.republic { font-size: 9px; margin-bottom: 1mm; }
.department { font-size: 9px; }
.form-title { margin-top: 2mm; font-weight: bold; font-size: 11.5px; }
.former { margin-top: .7mm; font-size: 7.5px; font-style: italic; }
.bar { height: 4.2mm; border: .35mm solid #000; background: #bfbfbf; text-align: center; font-weight: bold; padding-top: .5mm; font-size: 8.2px; }
.line-row { width: 100%; border-collapse: collapse; table-layout: fixed; }
.line-row td { height: 5.6mm; padding: 1mm 1mm .3mm; vertical-align: bottom; white-space: nowrap; }
.write { display: inline-block; border-bottom: .25mm solid #000; min-height: 3mm; margin-left: .5mm; padding: 0 .8mm .25mm; vertical-align: bottom; font-weight: bold; }
.eligibility { border-left: .25mm solid #000; border-right: .25mm solid #000; }
.eligibility td { height: 4.6mm; padding-top: .55mm; padding-bottom: .25mm; }
.box { display: inline-block; width: 2mm; height: 2mm; border: .25mm solid #000; margin: 0 2mm 0 .8mm; vertical-align: middle; }
.records { width: 100%; border-collapse: separate; border-spacing: 1.8mm 0; table-layout: fixed; }
.records > tbody > tr > td { width: 50%; vertical-align: top; padding: 0; }
.record { border: .35mm solid #000; height: 94mm; overflow: hidden; }
.meta { width: 100%; height: 16.8mm; border-collapse: collapse; table-layout: fixed; }
.meta td { padding: .7mm .8mm .15mm; height: 3.35mm; white-space: nowrap; font-size: 7.6px; }
.meta .write { height: 2.5mm; }
.grade { width: 100%; border-collapse: collapse; table-layout: fixed; }
.grade th, .grade td { border: .25mm solid #000; height: 3mm; padding: 0 .2mm; text-align: center; font-size: 7px; }
.grade thead th { height: 3.4mm; }
.grade th { font-weight: bold; }
.grade .area { width: 47%; text-align: left; font-weight: bold; }
.grade thead .area { text-align: center; }
.grade .q { width: 6%; }
.grade .final { width: 11%; }
.grade .remarks { width: 18%; }
.grade .sub { padding-left: 3mm; font-style: italic; font-weight: normal; }
.grade .general td { border-bottom: .7mm solid #000; font-weight: bold; }
.remedial-title, .remedial { width: 100%; border-collapse: collapse; table-layout: fixed; }
.remedial { border-bottom: .25mm solid #000; }
.remedial-title td, .remedial th, .remedial td { border: .25mm solid #000; text-align: center; height: 3.3mm; padding: 0; font-size:6.6px; }
.remedial-title td { font-weight: bold; }
.remedial td { height: 3.3mm; }
.remedial th { height: 5mm; line-height: 1; }
.remedial-block { width: 100%; }
.cert { border: .35mm solid #000; margin-top: 2mm; padding: 2.5mm 3mm; font-size: 8.2px; }
.cert-title { text-align:center; font-weight:bold; font-size:9px; margin-bottom:1.8mm; }
.signatures { width:100%; margin-top:6mm; border-collapse:collapse; table-layout:fixed; }
.signatures td { text-align:center; padding:0 8mm; }
.sigline { height:4mm; border-bottom:.25mm solid #000; }
.footer { position:absolute; right:0; bottom:0; font-size:6.5px; }
.scholastic-heading { margin-top: 1.5mm; }
</style>
</head>
<body>
@php
    $allRecords = collect($records ?? []);
    $frontRecords = collect(array_pad($allRecords->take(4)->all(), 4, null));
    $backRecords = collect(array_pad($allRecords->slice(4, 4)->all(), 4, null));
    $seal = public_path('images/deped-seal.jpg');
    $deped = public_path('images/deped-logo.jpg');
@endphp
<section class="page page-break">
    <div class="code">SF10-ES</div>
    <div class="masthead">
        <img class="seal" src="{{ $seal }}"><img class="deped" src="{{ $deped }}">
        <div class="republic">Republic of the Philippines</div><div class="department">Department of Education</div>
        <div class="form-title">Learner Permanent Record for Elementary School (SF10-ES)</div><div class="former">(Formerly Form 137)</div>
    </div>
    <div class="bar">LEARNER'S PERSONAL INFORMATION</div>
    <table class="line-row"><tr>
        <td style="width:30%">LAST NAME: <span class="write" style="width:72%">{{ $nameParts['last'] }}</span></td>
        <td style="width:31%">FIRST NAME: <span class="write" style="width:72%">{{ $nameParts['first'] }}</span></td>
        <td style="width:17%">NAME EXTN. (Jr,I,II) <span class="write" style="width:29%">{{ $nameParts['extension'] }}</span></td>
        <td style="width:22%">MIDDLE NAME: <span class="write" style="width:54%">{{ $nameParts['middle'] }}</span></td>
    </tr><tr>
        <td colspan="2">Learner Reference Number (LRN): <span class="write" style="width:63%">{{ $student?->lrn }}</span></td>
        <td>Birthdate (mm/dd/yyyy): <span class="write" style="width:38%"></span></td><td>Sex: <span class="write" style="width:70%"></span></td>
    </tr></table>
    <div class="bar">ELIGIBILITY FOR ELEMENTARY SCHOOL ENROLMENT</div>
    <table class="line-row eligibility">
        <tr><td><em>Credential Presented for Grade 1:</em> <span class="box"></span> Kinder Progress Report <span class="box"></span> ECCD Checklist <span class="box"></span> Kindergarten Certificate of Completion</td></tr>
        <tr><td>Name of School: <span class="write" style="width:36%"></span> School ID: <span class="write" style="width:15%"></span> Address of School: <span class="write" style="width:27%"></span></td></tr>
        <tr><td>Other Credential Presented</td></tr>
        <tr><td><span class="box"></span> PEPT Passer &nbsp; Rating: <span class="write" style="width:8%"></span> &nbsp; Date of Examination/Assessment (mm/dd/yyyy): <span class="write" style="width:17%"></span> <span class="box"></span> Others (Pls. Specify): <span class="write" style="width:19%"></span></td></tr>
        <tr><td style="padding-left:11mm">Name and Address of Testing Center: <span class="write" style="width:48%"></span> Remark: <span class="write" style="width:24%"></span></td></tr>
    </table>
    <div class="bar scholastic-heading">SCHOLASTIC RECORD</div>
    @include('pdf.partials.form137-record-grid', ['pageRecords' => $frontRecords])
    <div class="footer">SFRT Revised 2017</div>
</section>

<section class="page">
    <div class="code">SF10-ES</div><div class="page-no">Page 2 of 2</div>
    <div class="bar" style="margin-top:3mm">SCHOLASTIC RECORD</div>
    @include('pdf.partials.form137-record-grid', ['pageRecords' => $backRecords])
    <div class="bar" style="margin-top:2mm">FOR TRANSFER OUT / ELEMENTARY SCHOOL COMPLETER ONLY</div>
    <div class="cert"><div class="cert-title">CERTIFICATION</div>
        I CERTIFY that this is a true record of <span class="write" style="width:30%">{{ $student?->name }}</span> with LRN <span class="write" style="width:18%">{{ $student?->lrn }}</span> and that he / she is eligible for admission to Grade <span class="write" style="width:7%"></span>.
        <div style="margin-top:2mm">School Name: <span class="write" style="width:26%"></span> School ID: <span class="write" style="width:12%"></span> Division: <span class="write" style="width:18%"></span> Last School Year Attended: <span class="write" style="width:13%">{{ data_get($allRecords->last(), 'school_year') }}</span></div>
        <table class="signatures"><tr><td><div class="sigline"></div>Date</td><td><div class="sigline"></div>Name of Principal/School Head over Printed Name</td><td><div class="sigline"></div>(Affix School Seal here)</td></tr></table>
    </div>
    <div class="footer">SFRT Revised 2017</div>
</section>
</body></html>
