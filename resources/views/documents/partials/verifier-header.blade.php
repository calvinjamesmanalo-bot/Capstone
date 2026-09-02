<header class="verify-header">
    <div class="verify-shell verify-header-inner">
        <a class="verify-brand" href="{{ route('documents.lookup') }}">
            <img src="{{ asset(config('document_verification.logo')) }}" alt="{{ config('document_verification.issuer') }} seal">
            <span><strong>{{ config('document_verification.issuer') }}</strong><small>Secure Document Verification</small></span>
        </a>
        @if($showLookupLink ?? false)
            <a class="verify-header-link" href="{{ route('documents.lookup') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg><span>Verify another document</span>
            </a>
        @else
            <span class="verify-header-link" aria-label="Public verification portal">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 3 4 6v5c0 5 3.4 8.7 8 10 4.6-1.3 8-5 8-10V6l-8-3Z"/><path d="m9 12 2 2 4-4"/></svg><span>Public verification portal</span>
            </span>
        @endif
    </div>
</header>
