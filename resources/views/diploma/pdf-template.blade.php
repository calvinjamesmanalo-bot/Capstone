<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diploma Preview</title>
    <style>
        body {
            font-family: 'Georgia', serif;
            margin: 0;
            padding: 0;
            background-color: white;
            color: #333;
        }
        .diploma-container {
            width: 100%;
            height: 100%;
            padding: 40px;
            box-sizing: border-box;
            border: 15px double #b45309;
            text-align: center;
            position: relative;
        }
        .header {
            margin-bottom: 30px;
        }
        .school-name {
            font-size: 36px;
            font-weight: bold;
            color: #1e293b;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        .school-address {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 20px;
        }
        .award-text {
            font-size: 18px;
            font-style: italic;
            margin: 20px 0;
        }
        .student-name {
            font-size: 48px;
            font-weight: bold;
            color: #b45309;
            margin: 30px 0;
            text-decoration: underline;
            text-transform: uppercase;
        }
        .course-text {
            font-size: 24px;
            font-weight: bold;
            margin: 20px 0;
            text-transform: uppercase;
        }
        .date-text {
            font-size: 16px;
            margin-top: 40px;
        }
        .signatures {
            margin-top: 60px;
            display: table;
            width: 100%;
        }
        .signature-box {
            display: table-cell;
            width: 50%;
            padding: 0 40px;
        }
        .signature-line {
            border-top: 2px solid #333;
            margin-bottom: 5px;
        }
        .signer-name {
            font-weight: bold;
            font-size: 16px;
            text-transform: uppercase;
        }
        .signer-title {
            font-size: 12px;
            color: #64748b;
        }
        .seal {
            position: absolute;
            bottom: 40px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 100px;
            border: 4px solid #b45309;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: bold;
            color: #b45309;
            opacity: 0.3;
        }
    </style>
</head>
<body>
    <div class="diploma-container">
        <div class="header">
            <div class="school-name">Fiat Lux Academe</div>
            <div class="school-address">Dasmariñas City, Cavite, Philippines</div>
        </div>

        <div class="award-text">This certifies that</div>

        <div class="student-name">{{ $name }}</div>

        <div class="award-text">having satisfactorily completed the prescribed course of study is hereby awarded this</div>

        <div class="course-text">DIPLOMA</div>
        <div style="font-size: 18px; margin-top: -15px;">in</div>
        <div class="course-text">{{ $course }}</div>

        <div class="date-text">
            Given this {{ $date }} at Fiat Lux Academe, Dasmariñas City, Cavite.
        </div>

        <div class="signatures">
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signer-name">Marilyn Estipona</div>
                <div class="signer-title">School Registrar</div>
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signer-name">School Principal</div>
                <div class="signer-title">Academic Head</div>
            </div>
        </div>

        <div class="seal">OFFICIAL SEAL</div>
    </div>
</body>
</html>
