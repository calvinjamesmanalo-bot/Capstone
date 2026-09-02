<style>
    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        color: #111827;
        background: #eef2f7;
        font-family: Arial, Helvetica, sans-serif;
    }

    a {
        color: inherit;
    }

    .module-shell {
        min-height: 100vh;
        padding: 32px 18px;
    }

    .module-header,
    .module-grid,
    .preview-layout {
        width: min(1180px, 100%);
        margin: 0 auto;
    }

    .module-header {
        margin-bottom: 22px;
    }

    .eyebrow {
        margin: 0 0 8px;
        color: #1d4ed8;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0.12em;
        text-transform: uppercase;
    }

    .module-title {
        margin: 0;
        color: #0f172a;
        font-size: clamp(28px, 4vw, 44px);
        line-height: 1;
    }

    .module-subtitle {
        max-width: 760px;
        margin: 12px 0 0;
        color: #475569;
        font-size: 15px;
        line-height: 1.6;
    }

    .module-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 22px;
        align-items: start;
    }

    .form-panel {
        width: min(820px, 100%);
        margin: 0 auto;
    }

    .panel {
        border: 1px solid #dbe3ef;
        border-radius: 20px;
        background: #ffffff;
        box-shadow: 0 16px 40px rgba(15, 23, 42, 0.08);
    }

    .panel-pad {
        padding: 22px;
    }

    .panel-title {
        margin: 0 0 16px;
        color: #0f172a;
        font-size: 18px;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
    }

    .field-full {
        grid-column: 1 / -1;
    }

    label {
        display: block;
        margin-bottom: 7px;
        color: #334155;
        font-size: 13px;
        font-weight: 700;
    }

    input,
    select {
        width: 100%;
        border: 1px solid #cbd5e1;
        border-radius: 12px;
        padding: 12px 13px;
        color: #0f172a;
        background: #ffffff;
        font-size: 14px;
    }

    input:focus,
    select:focus {
        outline: 3px solid rgba(37, 99, 235, 0.16);
        border-color: #2563eb;
    }

    .help-text {
        margin: 6px 0 0;
        color: #64748b;
        font-size: 12px;
        line-height: 1.4;
    }

    .error-box {
        margin-bottom: 18px;
        border: 1px solid #fecaca;
        border-radius: 14px;
        padding: 12px 14px;
        color: #991b1b;
        background: #fef2f2;
        font-size: 13px;
        line-height: 1.5;
    }

    .actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 18px;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 42px;
        border: 1px solid transparent;
        border-radius: 999px;
        padding: 10px 18px;
        cursor: pointer;
        text-decoration: none;
        font-size: 14px;
        font-weight: 800;
    }

    .btn-primary {
        color: #ffffff;
        background: #0f172a;
    }

    .btn-secondary {
        color: #0f172a;
        border-color: #cbd5e1;
        background: #ffffff;
    }

    .btn-accent {
        color: #172554;
        background: #facc15;
    }

    .preview-layout {
        display: grid;
        grid-template-columns: 1fr;
        gap: 20px;
    }

    .preview-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding: 16px 18px;
    }

    .preview-toolbar h2 {
        margin: 0;
        color: #0f172a;
        font-size: 18px;
    }

    .certificate-wrap {
        width: 100%;
        overflow: auto;
        padding: 16px 0 36px;
    }

    .certificate-page {
        position: relative;
        width: 210mm;
        height: 297mm;
        min-height: 297mm;
        margin: 0 auto;
        overflow: hidden;
        padding: 0.54in 0.98in 0.58in;
        color: #000000;
        background: #ffffff;
        box-shadow: 0 24px 70px rgba(15, 23, 42, 0.22);
        font-family: 'Times New Roman', Times, serif;
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
    }

    .pdf-body {
        background: #ffffff;
    }

    .pdf-body .certificate-page {
        width: 210mm;
        height: 297mm;
        min-height: 297mm;
        margin: 0;
        padding: 0;
        box-shadow: none;
        transform: none !important;
        transform-origin: top left;
    }

    .pdf-body .certificate-inner {
        position: absolute;
        top: 0.54in;
        right: 0.98in;
        bottom: 0.58in;
        left: 0.98in;
        height: auto;
    }

    .certificate-template-layer {
        position: absolute;
        top: 0;
        left: 0;
        z-index: 0;
        display: block;
        width: 100%;
        height: 100%;
        overflow: visible;
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
    }

    .corner {
        position: absolute;
        z-index: 0;
        display: block;
        transform-origin: center;
    }

    .corner-navy-top {
        top: -0.24in;
        left: -0.47in;
        width: 1.52in;
        height: 1.98in;
        background: #000638;
        transform: skew(-42deg);
    }

    .corner-yellow-stroke-top {
        top: -0.22in;
        left: 1.72in;
        width: 0.026in;
        height: 2.12in;
        background: #ffd12f;
        transform: rotate(45deg);
    }

    .corner-yellow-top {
        top: 0.92in;
        left: -0.28in;
        width: 1.72in;
        height: 0.72in;
        background: #ffd52e;
        transform: skew(-43deg);
    }

    .corner-gray-top {
        top: -0.04in;
        left: 1.55in;
        width: 1.68in;
        height: 0.48in;
        background: #d9d9d9;
        transform: skew(-43deg);
    }

    .corner-navy-bottom {
        right: -0.47in;
        bottom: -0.24in;
        width: 1.52in;
        height: 1.98in;
        background: #000638;
        transform: skew(-42deg);
    }

    .corner-yellow-stroke-bottom {
        right: 1.12in;
        bottom: -0.30in;
        width: 0.026in;
        height: 2.12in;
        background: #ffd12f;
        transform: rotate(45deg);
    }

    .corner-yellow-bottom {
        right: -0.08in;
        bottom: 0.76in;
        width: 1.72in;
        height: 0.72in;
        background: #ffd52e;
        transform: skew(-43deg);
    }

    .corner-gray-bottom {
        right: 1.95in;
        bottom: -0.25in;
        width: 1.68in;
        height: 0.58in;
        background: #d9d9d9;
        transform: skew(-43deg);
    }

    .certificate-inner {
        position: relative;
        z-index: 1;
        height: 100%;
    }

    .school-header {
        position: relative;
        height: 1.58in;
        margin: 0;
        text-align: center;
    }

    .school-brand {
        position: absolute;
        top: 0.32in;
        left: 0;
        right: 0;
        color: #05056d;
    }

    .school-name {
    font-family: 'Times New Roman', Times, serif;
    font-size: 30px;      /* was 24px */
    font-weight: 900;
    letter-spacing: 0.02em;
    line-height: 1;
}

    .school-location {
    margin-top: 2px;      
    padding-left: 0;
    font-family: 'Times New Roman', Times, serif;
    font-size: 20px;      
    font-weight: 900;
    letter-spacing: 0;
    line-height: 1;
}

    .school-seal {
        position: absolute;
        top: 0.04in;
        left: 5.11in;
        width: 1.25in;
        height: 1.20in;
    }

    .school-seal img {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: contain;
    }

    .seal-fallback {
        position: relative;
        width: 100%;
        height: 100%;
        overflow: hidden;
        border: 2px solid #ffd642;
        border-radius: 50%;
        color: #ffd642;
        background: #03085f;
        font-family: Arial, Helvetica, sans-serif;
        font-weight: 900;
    }

    .seal-fallback::before {
        position: absolute;
        top: 0.17in;
        left: 0.17in;
        width: 0.48in;
        height: 0.48in;
        border: 2px solid #ffd642;
        border-radius: 50%;
        background: #ffffff;
        content: '';
    }

    .seal-ring,
    .seal-center,
    .seal-year {
        position: absolute;
        left: 0;
        right: 0;
        text-align: center;
    }

    .seal-ring {
        font-size: 5.6px;
        letter-spacing: 0.06em;
    }

    .seal-ring-top {
        top: 0.07in;
    }

    .seal-ring-bottom {
        bottom: 0.07in;
    }

    .seal-center {
        top: 0.34in;
        color: #03085f;
        font-size: 14px;
        letter-spacing: 0.04em;
    }

    .seal-year {
        top: 0.50in;
        color: #03085f;
        font-size: 6px;
        font-weight: 700;
    }

    .school-rule {
        height: 0;
        margin: 0 0.34in;
        border-top: 2px solid #080d38;
    }

    .certificate-title {
        margin: 0.66in 0 0.52in;
        text-align: center;
        font-size: 28px;
        font-weight: 900;
        letter-spacing: 0.30em;
        text-transform: uppercase;
    }

    .certificate-good_moral .certificate-title {
        margin-top: 0.36in;
        margin-bottom: 0.60in;
        font-size: 21px;
        letter-spacing: 0.02em;
    }

    .certificate-body {
        min-height: 3.28in;
        padding: 0;
        font-size: 16px;
        line-height: 1.55;
        text-align: justify;
    }

    .certificate-good_moral .certificate-body {
        min-height: 3.02in;
    }

    .certificate-body p {
        margin: 0 0 0.31in;
        text-indent: 0.55in;
    }

    .certificate-body strong {
        font-weight: 900;
    }

    .signature-block {
        position: absolute;
        top: 6.42in;
        right: 0.58in;
        display: block;
        width: 2.42in;
        margin: 0;
        padding: 0;
        text-align: center;
    }

    .signature-block > div {
        width: 100%;
    }

    .signature-label {
        margin: 0 0 0.44in;
        font-size: 14px;
        font-weight: 700;
    }

    .signature-name {
        min-width: 2.42in;
        margin: 0;
        font-size: 16px;
        font-weight: 900;
    }

    .signature-block .signature-name {
        min-width: 0;
    }

    .signature-position {
        margin: 2px 0 0;
        font-size: 15px;
    }

    .signature-block .signature-position {
        text-align: center;
    }

    .signed-label {
    position: absolute;
    top: 5.90in;   
    left: 0;
    margin: 0;
    padding: 0;
    font-size: 16px;
    font-weight: 700;
}

    .recognition-signatures {
    position: absolute;
    top: 6.85in;   
    left: 0;
    right: 0;
    height: 0.72in;
    text-align: center;
}
    .recognition-signature-left,
    .recognition-signature-right {
        position: absolute;
        top: 0;
        width: 2.05in;
        text-align: center;
    }

    .recognition-signature-left {
        left: 0;
    }

    .recognition-signature-right {
        right: 0;
    }

    .small-signature {
        min-width: 0;
        font-size: 14px;
        line-height: 1.15;
    }

    .principal-signature {
    position: absolute;
    top: 7.45in;   /* was 7.05in */
    left: 0;
    right: 0;
    margin: 0;
    text-align: center;
}

    .seal-note {
        position: absolute;
        left: 1.00in;
        bottom: 1.28in;
        z-index: 1;
        font-size: 9px;
        font-style: italic;
        line-height: 1.2;
    }

    .certificate-qr {
        position: absolute;
        bottom: 0.28in;
        left: 50%;
        z-index: 2;
        width: 1.65in;
        margin-left: -0.825in;
        text-align: center;
    }

    .certificate-qr > div {
        margin-top: 0 !important;
    }

    .certificate-qr img {
        width: 0.66in !important;
        height: 0.66in !important;
    }

    @page {
        size: A4 portrait;
        margin: 0;
    }

    @media (max-width: 980px) {
        .module-grid {
            grid-template-columns: 1fr;
        }

        .certificate-page {
            transform: scale(0.78);
            transform-origin: top left;
            margin-left: 0;
        }

        .certificate-wrap {
            min-height: 900px;
        }
    }

    @media (max-width: 680px) {
        .module-shell {
            padding: 22px 12px;
        }

        .panel-pad {
            padding: 18px;
        }

        .form-grid {
            grid-template-columns: 1fr;
        }

        .certificate-page {
            transform: scale(0.48);
        }

        .certificate-wrap {
            min-height: 560px;
        }
    }

    @media print {
        body {
            background: #ffffff;
        }

        .no-print {
            display: none !important;
        }

        .module-shell,
        .certificate-wrap {
            padding: 0;
        }

        .certificate-page {
            width: 210mm;
            min-height: 297mm;
            margin: 0;
            box-shadow: none;
            transform: none;
        }
    }
</style>
