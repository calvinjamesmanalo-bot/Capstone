<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Good Moral Certification</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 40px;
        }
        .header {
            text-align: center;
            margin-bottom: 50px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            text-transform: uppercase;
            color: #1e293b;
        }
        .header p {
            margin: 5px 0;
            font-size: 14px;
            color: #64748b;
        }
        .title {
            text-align: center;
            margin-bottom: 60px;
        }
        .title h2 {
            text-transform: uppercase;
            font-size: 28px;
            text-decoration: underline;
            letter-spacing: 2px;
        }
        .content {
            margin-bottom: 60px;
            font-size: 16px;
            text-align: justify;
        }
        .signature-section {
            margin-top: 100px;
            float: right;
            width: 250px;
            text-align: center;
        }
        .signature-line {
            border-top: 2px solid #000;
            margin-bottom: 5px;
        }
        .footer {
            position: absolute;
            bottom: 40px;
            left: 40px;
            right: 40px;
            text-align: center;
            font-size: 10px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>FIAT LUX ACADEME</h1>
        <p>Don Placido Campos Ave., Barangay San Jose, Dasmariñas City, Cavite</p>
    </div>

    <div class="title">
        <h2>Certification</h2>
    </div>

    <div class="content">
        <p style="margin-bottom: 30px;"><strong>TO WHOM IT MAY CONCERN:</strong></p>

        <p>This is to certify that <strong>{{ strtoupper($name) }}</strong> is a student of this institution and has been found to be of good moral character. He/She has not been involved in any derogatory activities and has complied with the rules and regulations of this Academy.</p>

        <p>This certification is issued upon the request of the above-named student for <strong>{{ $purpose }}</strong>.</p>

        <p>Given this {{ $date }} at Fiat Lux Academe, Dasmariñas City, Cavite, Philippines.</p>
    </div>

    <div class="signature-section">
        <div class="signature-line"></div>
        <p style="margin: 0; font-weight: bold; text-transform: uppercase;">Marilyn Estipona</p>
        <p style="margin: 0; font-size: 12px; color: #64748b;">School Registrar</p>
    </div>

    <div class="footer">
        <p>This is a computer-generated document. No signature is required unless otherwise specified.</p>
    </div>
    @include('documents.partials.qr', array_merge([
        'qrDocumentType' => 'Certificate of Good Moral Character',
        'qrSubject' => $name,
        'qrPurpose' => $purpose,
    ], $qrContext ?? []))
</body>
</html>
