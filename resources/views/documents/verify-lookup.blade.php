<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>Verify a document &middot; {{ config('document_verification.issuer') }}</title>
    @include('documents.partials.verifier-theme')
    @include('partials.responsive-foundation')
</head>
<body class="verify-page">
@include('documents.partials.verifier-header')

<main class="verify-shell verify-main">
    <div class="lookup-page-heading">
        <p>Office of the Registrar</p>
        <h1>Document verification</h1>
        <span>Check whether a document was issued by {{ config('document_verification.issuer') }}.</span>
    </div>

    <div class="lookup-layout">
        <section class="lookup-card" aria-labelledby="verification-form-title">
            <div class="lookup-card-heading">
                <span class="lookup-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M7 3h7l4 4v14H7z"/><path d="M14 3v5h5M10 13h5M10 17h5"/></svg></span>
                <div>
                    <h2 id="verification-form-title">Enter the document control number</h2>
                    <p>The control number is printed on the document, usually beside the QR code.</p>
                </div>
            </div>
            @if ($notFound)
                <div class="verify-error">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5m0 3h.01"/></svg>
                    <span>No issued document matches <strong>{{ $reference }}</strong>. Check the number and try again.</span>
                </div>
            @endif
            <form class="lookup-form" method="GET" action="{{ route('documents.lookup') }}">
                <label for="reference">Control number</label>
                <div class="lookup-input-wrap">
                    <input id="reference" name="reference" value="{{ $reference }}" placeholder="FLA-2026-XXXXXXXX" required autocomplete="off" spellcheck="false">
                </div>
                <button class="primary-button" type="submit">
                    Check document
                </button>
            </form>
        </section>

        <aside class="lookup-help" aria-labelledby="lookup-help-title">
            <h2 id="lookup-help-title">Before you continue</h2>
            <ol>
                <li>Enter the complete control number, including the dashes.</li>
                <li>Compare the name and document details shown in the result.</li>
                <li>Make sure the status is marked <strong>Authentic</strong>.</li>
            </ol>
            <p>For printed copies, you may also scan the QR code to open the verification record directly.</p>
        </aside>
    </div>
</main>

<footer class="verify-footer">&copy; {{ date('Y') }} <strong>{{ config('document_verification.issuer') }}</strong> &middot; Public verification results are recorded for security auditing.</footer>
@include('partials.loading-overlay')
</body>
</html>
