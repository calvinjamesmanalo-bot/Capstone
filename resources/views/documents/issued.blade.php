@extends('layouts.app')

@section('title', 'Official Document')
@section('page_title', 'Official Document')
@section('page_subtitle', $document->control_number)

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 font-semibold text-emerald-800">{{ session('success') }}</div>
    @endif

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="bg-emerald-600 px-7 py-6 text-white">
            <p class="text-xs font-black uppercase tracking-[0.22em]">Document successfully issued</p>
            <h1 class="mt-2 text-3xl font-black">{{ $document->control_number }}</h1>
            <p class="mt-2 text-sm text-emerald-50">The immutable official PDF is stored and ready for authorized download.</p>
        </div>

        <dl class="grid gap-px bg-slate-200 md:grid-cols-2">
            @foreach([
                'Document type' => $document->document_type,
                'Holder' => $document->holder_name,
                'Issuance status' => strtoupper($document->status),
                'PDF digital signature' => $document->pdf_signature_status === 'signed' ? 'SIGNED (X.509 / PKCS#7)' : strtoupper($document->pdf_signature_status),
                'Issued' => $document->pdf_signed_at?->format('F j, Y g:i A') ?? $document->issued_at->format('F j, Y g:i A'),
                'Issued by' => $document->issuedBy?->display_name ?? 'System',
                'Blockchain proof' => strtoupper($document->blockchain_status),
                'Certificate SHA-256 fingerprint' => $document->pdf_certificate_fingerprint ?: 'Not available',
            ] as $label => $value)
                <div class="bg-white px-6 py-5">
                    <dt class="text-xs font-black uppercase tracking-wider text-slate-400">{{ $label }}</dt>
                    <dd class="mt-1 break-all font-bold text-slate-800">{{ $value }}</dd>
                </div>
            @endforeach
        </dl>

        @if($document->blockchain_status === 'unavailable')
            <div class="border-t border-amber-200 bg-amber-50 px-6 py-4 text-sm text-amber-900">
                No blockchain adapter exists in this project, so no blockchain transaction is claimed. The embedded PDF signature, SHA-256 file hash, QR verification, and audit trail remain active.
            </div>
        @endif

        @if($document->officialArtifact)
            <div class="border-t border-slate-200 px-6 py-5">
                <p class="text-xs font-black uppercase tracking-wider text-slate-400">Final signed PDF SHA-256</p>
                <p class="mt-2 break-all font-mono text-sm text-slate-800">{{ strtoupper($document->officialArtifact->sha256_hash) }}</p>
            </div>
        @endif

        <div class="flex flex-wrap gap-3 border-t border-slate-200 bg-slate-50 px-6 py-5">
            @if($document->status === 'valid' && $document->officialArtifact)
                <a href="{{ route('documents.download', $document) }}" class="rounded-xl bg-[#000638] px-5 py-3 text-sm font-black uppercase tracking-wider text-white">Download official signed PDF</a>
            @endif
            <a href="{{ app(\App\Support\DocumentQrCode::class)->verificationUrl($document) }}" target="_blank" class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-black uppercase tracking-wider text-slate-700">Open public verification</a>
            <a href="{{ route('requests.index') }}" class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-black uppercase tracking-wider text-slate-700">Back to requests</a>
        </div>
    </section>

    @if($document->status === 'valid' && in_array(auth()->user()->role, ['admin', 'registrar', 'records_officer']))
        <section class="rounded-2xl border border-red-200 bg-white p-6">
            <h2 class="text-lg font-black text-red-800">Revoke official document</h2>
            <p class="mt-1 text-sm text-slate-600">The original issuance and PDF stay in history. A correction must be finalized as a new document ID.</p>
            <form method="POST" action="{{ route('documents.revoke', $document) }}" class="mt-4 flex flex-col gap-3 sm:flex-row" onsubmit="return confirm('Revoke this official document? This cannot be silently undone.');">
                @csrf
                <input name="reason" required maxlength="255" placeholder="Required reason for revocation" class="flex-1 rounded-xl border border-slate-300 px-4 py-3 text-sm">
                <button class="rounded-xl bg-red-600 px-5 py-3 text-sm font-black uppercase tracking-wider text-white">Revoke</button>
            </form>
        </section>
    @endif
</div>
@endsection
