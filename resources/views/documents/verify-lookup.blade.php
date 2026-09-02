<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>Verify a document &middot; {{ config('document_verification.issuer') }}</title>
    @include('documents.partials.verifier-theme')
</head>
<body class="verify-page">
@include('documents.partials.verifier-header')

<main class="verify-shell verify-main">
    <div class="lookup-layout">
        <section class="lookup-intro">
            <p class="eyebrow">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 3 4 6v5c0 5 3.4 8.7 8 10 4.6-1.3 8-5 8-10V6l-8-3Z"/><path d="m9 12 2 2 4-4"/></svg>
                Official document verifier
            </p>
            <h1>Confirm a school document with confidence.</h1>
            <p>Use the printed control number or scan the QR code to check the issuer record, document status, and cryptographic proof.</p>
            <div class="trust-list">
                <div class="trust-item"><span class="trust-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 12 4 4L19 6"/></svg></span>Signed issuer record verification</div>
                <div class="trust-item"><span class="trust-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16v16H4zM8 8h8v8H8z"/></svg></span>SHA-256 protected document data</div>
                <div class="trust-item"><span class="trust-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M4 7h16M4 17h16"/></svg></span>Audited verification activity</div>
            </div>
        </section>

        <section class="lookup-card">
            <span class="lookup-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg></span>
            <h2>Verify a document</h2>
            <p>Enter the control number exactly as printed on the official copy.</p>
            @if ($notFound)
                <div class="verify-error">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5m0 3h.01"/></svg>
                    <span>No issued document matches <strong>{{ $reference }}</strong>. Check the number and try again.</span>
                </div>
            @endif
            <form class="lookup-form" method="GET" action="{{ route('documents.lookup') }}">
                <label for="reference">Document control number</label>
                <div class="lookup-input-wrap">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 4h16v16H4zM8 8h8v8H8z"/></svg>
                    <input id="reference" name="reference" value="{{ $reference }}" placeholder="FLA-2026-XXXXXXXX" required autocomplete="off" spellcheck="false">
                </div>
                <button class="primary-button" type="submit">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 3 4 6v5c0 5 3.4 8.7 8 10 4.6-1.3 8-5 8-10V6l-8-3Z"/><path d="m9 12 2 2 4-4"/></svg>
                    Verify document
                </button>
            </form>
            <p class="lookup-hint"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5m0-8h.01"/></svg><span>Always compare the displayed holder and document details with the copy presented to you.</span></p>
        </section>
    </div>
</main>

<footer class="verify-footer">&copy; {{ date('Y') }} <strong>{{ config('document_verification.issuer') }}</strong> &middot; Public verification results are recorded for security auditing.</footer>
@include('partials.loading-overlay')
</body>
</html>
