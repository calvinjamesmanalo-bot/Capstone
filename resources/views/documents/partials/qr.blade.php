@php
    $documentQr = $documentQr ?? app(\App\Support\DocumentQrCode::class)->make($qrDocumentType, $qrSubject ?? '', [
        'request_id' => $qrRequestId ?? null,
        'holder_identifier' => $qrHolderIdentifier ?? null,
        'purpose' => $qrPurpose ?? null,
        'issued_at' => $qrIssuedAt ?? now(),
        'expires_at' => $qrExpiresAt ?? null,
        'fields' => $qrFields ?? [],
    ]);
@endphp
<div style="margin-top: 12px; text-align: center; page-break-inside: avoid;">
    <img src="{{ $documentQr['data_uri'] }}" alt="Verification QR code" style="display: inline-block; width: 78px; height: 78px;">
    <div style="margin-top: 2px; font-family: Arial, sans-serif; font-size: 7px; line-height: 1.3; color: #334155;">
        SCAN TO VERIFY · {{ $documentQr['reference'] }}<br>
        @if($qrPdfWillBeSigned ?? false)
            PDF DIGITALLY SIGNED · {{ config('document_verification.issuer') }}<br>
        @else
            SIGNED VERIFICATION RECORD · {{ config('document_verification.issuer') }}<br>
        @endif
        SHA-256 RECORD: {{ strtoupper(substr($documentQr['document']->content_hash, 0, 16)) }}
        @if($qrExpiresAt ?? null)<br>Valid until {{ \Illuminate\Support\Carbon::parse($qrExpiresAt)->format('M d, Y') }}@endif
    </div>
</div>
