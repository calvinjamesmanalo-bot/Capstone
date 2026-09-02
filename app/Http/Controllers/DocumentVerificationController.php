<?php

namespace App\Http\Controllers;

use App\Models\DocumentAuthenticity;
use App\Models\DocumentVerificationAudit;
use App\Support\DocumentQrCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentVerificationController extends Controller
{
    public function lookup(Request $request, DocumentQrCode $qr): View|RedirectResponse
    {
        $reference = strtoupper(trim((string) $request->query('reference')));

        if ($reference !== '') {
            $document = DocumentAuthenticity::where('control_number', $reference)->first();

            if ($document) {
                return redirect()->to($qr->verificationUrl($document));
            }
        }

        return view('documents.verify-lookup', [
            'reference' => $reference,
            'notFound' => $reference !== '',
        ]);
    }

    public function show(Request $request, string $identifier, DocumentQrCode $qr): View
    {
        $document = DocumentAuthenticity::with('requestDocument')
            ->withCount('artifacts')
            ->where(function ($query) use ($identifier) {
                $query->where('verification_token', $identifier)
                    ->orWhere('control_number', strtoupper($identifier));
            })
            ->first();

        if (! $document) {
            $this->audit($request, null, 'not_found');

            return view('documents.verify', [
                'document' => null,
                'verification' => ['authentic' => false, 'result' => 'not_found'],
            ]);
        }

        $verification = $qr->verify($document, $request->query('signature'));
        $this->audit($request, $document, $verification['result']);

        return view('documents.verify', compact('document', 'verification'));
    }

    public function verifyFile(Request $request, string $identifier, DocumentQrCode $qr): View
    {
        $document = DocumentAuthenticity::with('requestDocument')
            ->withCount('artifacts')
            ->where(function ($query) use ($identifier) {
                $query->where('verification_token', $identifier)
                    ->orWhere('control_number', strtoupper($identifier));
            })
            ->first();

        if (! $document) {
            $this->audit($request, null, 'not_found');

            return view('documents.verify', [
                'document' => null,
                'verification' => ['authentic' => false, 'result' => 'not_found'],
            ]);
        }

        $verification = $qr->verify($document, $request->input('signature'));

        if (! $verification['signature_valid'] || ! $verification['content_hash_valid']) {
            $this->audit($request, $document, $verification['result']);

            return view('documents.verify', compact('document', 'verification'));
        }

        $validated = $request->validate([
            'document_file' => ['required', 'file', 'mimes:pdf,xlsx', 'max:20480'],
            'signature' => ['required', 'string'],
        ], [
            'document_file.required' => 'Select the original PDF or XLSX file to verify.',
            'document_file.mimes' => 'Only PDF and XLSX documents can be verified.',
        ]);

        $fileVerification = $qr->verifyArtifact(
            $document,
            $validated['document_file']->getRealPath()
        );
        $fileVerification['uploaded_name'] = $validated['document_file']->getClientOriginalName();
        $this->audit($request, $document, $fileVerification['result']);

        return view('documents.verify', compact('document', 'verification', 'fileVerification'));
    }

    public function revoke(Request $request, DocumentAuthenticity $document): RedirectResponse
    {
        abort_unless(in_array($request->user()?->role, ['admin', 'registrar', 'records_officer'], true), 403);
        abort_unless($document->status === 'valid', 409, 'Only a valid issued document can be revoked.');

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $document->update([
            'status' => 'revoked',
            'revoked_at' => now(),
            'revoked_by' => $request->user()->id,
            'revocation_reason' => $validated['reason'],
        ]);
        if ($document->requestDocument && $document->requestDocument->status !== 'rejected') {
            $document->requestDocument->update(['status' => 'processed']);
        }

        record_log(
            'Revoked Document',
            'Document Verification',
            "Revoked {$document->control_number}: {$validated['reason']}"
        );

        return back()->with('success', "Document {$document->control_number} has been revoked.");
    }

    public function issued(Request $request, DocumentAuthenticity $document): View
    {
        $this->authorizeDocumentAccess($request, $document);
        $document->load(['officialArtifact', 'issuedBy', 'reissuedFrom', 'replacement']);

        return view('documents.issued', compact('document'));
    }

    public function downloadOfficial(Request $request, DocumentAuthenticity $document): BinaryFileResponse
    {
        $this->authorizeDocumentAccess($request, $document);
        if ($document->status !== 'valid' && $request->user()?->role === 'student') {
            abort(410, 'This official document is no longer valid.');
        }
        $artifact = $document->officialArtifact()->firstOrFail();
        abort_unless($artifact->storage_disk && $artifact->storage_path, 404);
        abort_unless(str_starts_with($artifact->storage_path, 'issued-documents/') && ! str_contains($artifact->storage_path, '..'), 404);
        abort_unless(Storage::disk($artifact->storage_disk)->exists($artifact->storage_path), 404);

        $filename = Str::of($artifact->original_filename ?: $document->control_number.'.pdf')
            ->replace(['/', '\\'], '-')
            ->toString();

        return response()->download(
            Storage::disk($artifact->storage_disk)->path($artifact->storage_path),
            $filename,
            ['Content-Type' => 'application/pdf']
        );
    }

    private function authorizeDocumentAccess(Request $request, DocumentAuthenticity $document): void
    {
        $user = $request->user();
        $isStaff = in_array($user?->role, ['admin', 'registrar', 'records_officer'], true);
        $isOwner = $user?->role === 'student'
            && $user->student_number
            && hash_equals((string) $document->holder_identifier, (string) $user->student_number);

        abort_unless($isStaff || $isOwner, 403);
    }

    private function audit(Request $request, ?DocumentAuthenticity $document, string $result): void
    {
        DocumentVerificationAudit::create([
            'document_authenticity_id' => $document?->id,
            'user_id' => $request->user()?->id,
            'control_number' => $document?->control_number,
            'result' => $result,
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 1000),
            'verified_at' => now(),
        ]);
    }
}
