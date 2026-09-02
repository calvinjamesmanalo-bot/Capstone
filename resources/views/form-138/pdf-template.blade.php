<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Form 138 - {{ $student->name ?? $student_name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 16px;
            margin: 0;
            text-transform: uppercase;
        }
        .header p {
            margin: 2px 0;
        }
        .info-table {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 5px;
            border-bottom: 1px solid #eee;
        }
        .label {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
            color: #666;
        }
        .value {
            font-weight: bold;
            font-size: 12px;
        }
        .grades-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .grades-table th, .grades-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
        }
        .grades-table th {
            background-color: #f5f5f5;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
        }
        .subject-name {
            text-align: left !important;
            font-weight: bold;
        }
        .footer {
            margin-top: 50px;
        }
        .footer-table {
            width: 100%;
        }
        .signature-line {
            border-top: 1px solid #000;
            margin-top: 40px;
            text-align: center;
            padding-top: 5px;
            width: 200px;
        }
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 100px;
            color: rgba(0, 0, 0, 0.05);
            z-index: -1;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <div class="watermark">DRAFT · NOT YET OFFICIALLY ISSUED</div>

    <div class="container">
        <div class="header">
            <p>Republic of the Philippines</p>
            <p>Department of Education</p>
            <h1>{{ \App\Models\Setting::where('key', 'institution_name')->first()->value ?? 'Fiat Lux Academe' }}</h1>
            <p>Report on Learning Progress and Achievements</p>
            <p><strong>FORM 138-A</strong></p>
        </div>

        <table class="info-table">
            <tr>
                <td width="50%">
                    <span class="label">Name:</span><br>
                    <span class="value">{{ $student->name ?? $student_name }}</span>
                </td>
                <td width="25%">
                    <span class="label">Age:</span><br>
                    <span class="value">{{ $student->age ?? 'N/A' }}</span>
                </td>
                <td width="25%">
                    <span class="label">Sex:</span><br>
                    <span class="value">{{ $student->gender ?? 'N/A' }}</span>
                </td>
            </tr>
            <tr>
                <td>
                    <span class="label">Grade & Section:</span><br>
                    <span class="value">{{ $grade_level }}</span>
                </td>
                <td>
                    <span class="label">School Year:</span><br>
                    <span class="value">{{ $school_year }}</span>
                </td>
                <td>
                    <span class="label">LRN:</span><br>
                    <span class="value">{{ $student->student_number ?? 'N/A' }}</span>
                </td>
            </tr>
        </table>

        <table class="grades-table">
            <thead>
                <tr>
                    <th rowspan="2" width="40%">Learning Areas</th>
                    <th colspan="4">Quarter</th>
                    <th rowspan="2">Final Rating</th>
                    <th rowspan="2">Remarks</th>
                </tr>
                <tr>
                    <th width="10%">1</th>
                    <th width="10%">2</th>
                    <th width="10%">3</th>
                    <th width="10%">4</th>
                </tr>
            </thead>
            <tbody>
                @php $totalFinal = 0; $count = 0; @endphp
                @foreach($subjects as $subject)
                    @php 
                        $final = $subject['final'] ?? null;
                        if($final) { $totalFinal += $final; $count++; }
                    @endphp
                    <tr>
                        <td class="subject-name">{{ $subject['name'] }}</td>
                        <td>{{ $subject['q1'] ?? '' }}</td>
                        <td>{{ $subject['q2'] ?? '' }}</td>
                        <td>{{ $subject['q3'] ?? '' }}</td>
                        <td>{{ $subject['q4'] ?? '' }}</td>
                        <td style="font-weight: bold;">{{ $final ?? '' }}</td>
                        <td>{{ $final >= 75 ? 'Passed' : ($final ? 'Failed' : '') }}</td>
                    </tr>
                @endforeach
                <tr style="background-color: #f9f9f9; font-weight: bold;">
                    <td class="subject-name" colspan="5" style="text-align: right !important; padding-right: 20px;">GENERAL AVERAGE</td>
                    <td>{{ $count > 0 ? round($totalFinal / $count, 2) : '' }}</td>
                    <td>{{ ($count > 0 && ($totalFinal / $count) >= 75) ? 'Passed' : '' }}</td>
                </tr>
            </tbody>
        </table>

        <div class="footer">
            <table class="footer-table">
                <tr>
                    <td width="50%">
                        <div class="signature-line">
                            <strong>{{ auth()->user()->display_name }}</strong><br>
                            Records Officer
                        </div>
                    </td>
                    <td width="50%" align="right">
                        <div class="signature-line" style="margin-left: auto;">
                            <strong>{{ \App\Models\User::where('role', 'registrar')->first()->display_name ?? 'The Registrar' }}</strong><br>
                            School Registrar
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div style="margin-top: 30px; font-size: 9px; color: #888; text-align: center;">
            <p>This is a computer-generated document. Any alteration voids this certificate.</p>
            <p>Generated on: {{ date('F d, Y h:i A') }}</p>
        </div>
    </div>
</body>
</html>
