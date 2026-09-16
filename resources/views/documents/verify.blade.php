@php
    $result = $verification['result'];
    $isAuthentic = $verification['authentic'] ?? false;
    $messages = [
        'authentic' => ['Issuance record is authentic', 'The QR, issuer signature, document status, and protected record are valid. You may also upload the original digital file for exact-file checking.'],
        'expired' => ['Document has expired', 'This record was issued by the school, but its validity period has ended.'],
        'revoked' => ['Document has been revoked', $document?->revocation_reason ?: 'Contact the issuing office for more information.'],
        'superseded' => ['Document has been superseded', 'A newer version of this document has been issued.'],
        'tampered' => ['Verification data mismatch', 'The signed payload or protected document data no longer matches the issuer record.'],
        'invalid_link' => ['Invalid verification link', 'The QR signature is missing or does not match this document.'],
        'not_found' => ['Document not found', 'No issued record matches this verification token.'],
        'invalid' => ['Document is not valid', 'Contact the issuing office for more information.'],
    ];
    [$title, $message] = $messages[$result] ?? $messages['invalid'];
    $resultClass = array_key_exists($result, $messages) ? $result : 'invalid';
    $signatureValid = $verification['signature_valid'] ?? false;
    $contentHashValid = $verification['content_hash_valid'] ?? false;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ $title }} &middot; {{ config('document_verification.issuer') }}</title>
    @include('documents.partials.verifier-theme')
    @include('partials.responsive-foundation')
</head>
<body class="verify-page">
@include('documents.partials.verifier-header', ['showLookupLink' => true])

<main class="result-container verify-main">
    <section class="result-banner {{ $resultClass }}">
        <span class="result-icon">
            @if($isAuthentic)
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>
            @else
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M12 8v5m0 3h.01"/></svg>
            @endif
        </span>
        <div>
            <p class="result-kicker">Verification result</p>
            <h1>{{ $title }}</h1>
            <p class="result-message">{{ $message }}</p>
        </div>
    </section>

    <div class="evidence-strip" aria-label="Verification checks">
        <div class="evidence-item {{ $signatureValid ? 'valid' : '' }}"><span class="evidence-dot"></span>Issuer signature {{ $signatureValid ? 'valid' : 'invalid' }}</div>
        <div class="evidence-item {{ $contentHashValid ? 'valid' : '' }}"><span class="evidence-dot"></span>Protected record {{ $contentHashValid ? 'matches' : 'mismatch' }}</div>
        <div class="evidence-item {{ $isAuthentic ? 'valid' : '' }}"><span class="evidence-dot"></span>Status: {{ ucfirst(str_replace('_', ' ', $result)) }}</div>
    </div>

    @if ($document)
        <section class="document-card">
            <div class="card-heading">
                <div><h2>Document details</h2><p>Compare these details with the presented document.</p></div>
                <span class="control-badge">{{ $document->control_number }}</span>
            </div>

            <div class="detail-grid">
                <div class="detail-item"><span class="detail-label">Document type</span><span class="detail-value">{{ $document->document_type }}</span></div>
                <div class="detail-item"><span class="detail-label">Holder name</span><span class="detail-value">{{ $document->holder_name }}</span></div>
                @if ($document->holder_identifier)<div class="detail-item"><span class="detail-label">Holder / student ID</span><span class="detail-value">{{ $document->holder_identifier }}</span></div>@endif
                @if ($document->purpose)<div class="detail-item"><span class="detail-label">Purpose</span><span class="detail-value">{{ $document->purpose }}</span></div>@endif
                <div class="detail-item"><span class="detail-label">Issue date</span><span class="detail-value">{{ $document->issued_at->format('F j, Y') }}</span></div>
                <div class="detail-item"><span class="detail-label">Valid until</span><span class="detail-value">{{ $document->expires_at?->format('F j, Y') ?? 'No stated expiry' }}</span></div>
                <div class="detail-item"><span class="detail-label">Issuing institution</span><span class="detail-value">{{ $document->issuer_name }}</span></div>
                <div class="detail-item"><span class="detail-label">Record status</span><span class="detail-value">{{ ucfirst($document->status) }}</span></div>
            </div>

            <details class="technical">
                <summary>Technical verification evidence</summary>
                <dl class="technical-list">
                    <div class="technical-row"><dt>Issuer signature</dt><dd>{{ $signatureValid ? 'Valid' : 'Invalid' }} &middot; {{ $document->signature_algorithm }}</dd></div>
                    <div class="technical-row"><dt>Protected record SHA-256</dt><dd><code>{{ strtoupper($document->content_hash) }}</code></dd></div>
                    <div class="technical-row"><dt>Issuer key fingerprint</dt><dd><code>{{ $document->issuer_key_fingerprint }}</code></dd></div>
                    <div class="technical-row"><dt>Embedded PDF signature</dt><dd>{{ $document->pdf_signature_status === 'signed' ? 'Signed · X.509 / PKCS#7' : 'Not available for this copy' }}</dd></div>
                    @if($document->pdf_certificate_fingerprint)<div class="technical-row"><dt>PDF certificate SHA-256</dt><dd><code>{{ $document->pdf_certificate_fingerprint }}</code></dd></div>@endif
                    <div class="technical-row"><dt>Blockchain proof</dt><dd>{{ strtoupper($document->blockchain_status ?? 'unavailable') }}</dd></div>
                </dl>
            </details>
        </section>

        <p class="acceptance-warning">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 9v4m0 4h.01"/><path d="M10.3 3.9 2.6 17.2A2 2 0 0 0 4.3 20h15.4a2 2 0 0 0 1.7-2.8L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg>
            <span><strong>Compare before accepting:</strong> the holder name, document type, dates, and control number must match the paper. Report any mismatch or visible alteration to the issuing office.</span>
        </p>

        @if($signatureValid && $contentHashValid)
            <section class="file-check">
                <div class="file-check-heading">
                    <span class="file-check-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 3 4 6v5c0 5 3.4 8.7 8 10 4.6-1.3 8-5 8-10V6l-8-3Z"/><path d="m9 12 2 2 4-4"/></svg></span>
                    <div><h2>Check the actual digital file</h2><p>Upload the original PDF or XLSX to compare every byte with the official copy stored by RegHub.</p></div>
                </div>

                @isset($fileVerification)
                    @if($fileVerification['matches'])
                        <div class="file-result file-match"><strong>Exact official-file match.</strong>{{ $fileVerification['uploaded_name'] }} has not changed since RegHub generated it.<br><code>SHA-256: {{ strtoupper($fileVerification['sha256_hash']) }}</code></div>
                    @else
                        <div class="file-result file-mismatch"><strong>Altered or unrecognized file.</strong>{{ $fileVerification['uploaded_name'] }} does not match any official file issued under this control number.<br><code>Uploaded SHA-256: {{ strtoupper($fileVerification['sha256_hash']) }}</code></div>
                    @endif
                @endisset

                @if($document->artifacts_count > 0)
                    @if($errors->any())<ul class="error-list">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
                    <form method="POST" enctype="multipart/form-data" action="{{ route('documents.verify-file', ['identifier' => $document->control_number]) }}">
                        @csrf
                        <input type="hidden" name="signature" value="{{ request('signature') }}">
                        <input type="file" name="document_file" accept=".pdf,.xlsx,application/pdf,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
                        <button class="primary-button" type="submit">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 16V4m0 0L7 9m5-5 5 5"/><path d="M5 14v5h14v-5"/></svg>
                            Upload and check file
                        </button>
                    </form>
                    <p><strong>Important:</strong> use the original digital download. A printed-and-scanned copy creates a different file and will not match.</p>
                @else
                    <div class="file-result file-mismatch"><strong>Exact-file checking unavailable.</strong>This older copy has no registered official file hash. Generate it again to enable exact-file checking.</div>
                @endif
            </section>
        @endif
    @endif

    <footer class="result-footer">
        <span>Checked {{ now()->format('F j, Y · g:i A T') }} and recorded for audit purposes.</span>
        <a href="{{ route('documents.lookup') }}">Verify another control number &rarr;</a>
    </footer>
</main>

@include('partials.loading-overlay')
</body>
</html>
