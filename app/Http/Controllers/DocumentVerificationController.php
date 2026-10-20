<?php

namespace App\Http\Controllers;

use App\Models\DocumentAuthenticity;
use App\Models\DocumentVerificationAudit;
use App\Support\DocumentQrCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
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

    public function verifyPhotoText(Request $request, string $identifier, DocumentQrCode $qr): JsonResponse
    {
        $document = DocumentAuthenticity::with('requestDocument')
            ->where(fn ($query) => $query->where('verification_token', $identifier)
                ->orWhere('control_number', strtoupper($identifier)))
            ->firstOrFail();
        $verification = $qr->verify($document, $request->input('signature'));
        abort_unless($verification['authentic'], 422, 'The signed verification record is not valid.');

        $validated = $request->validate(['ocr_text' => ['required', 'string', 'min:20', 'max:100000']]);
        $result = $this->comparePhotoText($document, $validated['ocr_text']);
        $this->audit($request, $document, 'photo_'.$result['status']);

        return response()->json($result);
    }

    private function comparePhotoText(DocumentAuthenticity $document, string $ocrText): array
    {
        $normalize = static fn (string $value): string => trim(preg_replace('/\s+/u', ' ', mb_strtoupper(preg_replace('/[^\pL\pN]+/u', ' ', $value))) ?? '');
        $lines = array_values(array_filter(array_map($normalize, preg_split('/\R/u', $ocrText) ?: [])));
        $allText = implode(' ', $lines);
        $identityChecks = array_filter([
            $normalize($document->holder_name),
            $normalize((string) $document->holder_identifier),
            $normalize($document->control_number),
        ]);
        $identityMatches = collect($identityChecks)->filter(fn ($expected) => str_contains($allText, $expected))->count();

        $fields = $document->source_data['fields'] ?? [];
        $grades = is_array($fields['grades'] ?? null) ? $fields['grades'] : [];
        $checkedRows = 0;
        $mismatchedRows = 0;
        foreach ($grades as $subject => $periods) {
            if (! is_array($periods)) continue;
            $subjectNeedle = $normalize((string) $subject);
            if (mb_strlen($subjectNeedle) < 2) continue;
            $lineIndex = collect($lines)->search(fn ($line) => str_contains($line, $subjectNeedle));
            if ($lineIndex === false) {
                $mismatchedRows++;
                continue;
            }
            $checkedRows++;
            $nearby = implode(' ', array_slice($lines, max(0, $lineIndex - 1), 3));
            $expectedGrades = array_values(array_filter(array_map(
                fn ($grade) => is_numeric($grade) ? (string) (int) round((float) $grade) : null,
                $periods
            )));
            foreach ($expectedGrades as $grade) {
                if (! preg_match('/(?<!\d)'.preg_quote($grade, '/').'(?!\d)/', $nearby)) {
                    $mismatchedRows++;
                    break;
                }
            }
        }

        if (mb_strlen($allText) < 80 || $identityMatches === 0 || ($grades !== [] && $checkedRows < max(2, (int) floor(count($grades) * .35)))) {
            return ['status' => 'unreadable', 'title' => 'Photo is not clear enough', 'message' => 'Retake a straight, brighter photo showing the entire document. No official grades were exposed.'];
        }
        if ($mismatchedRows > 0 || $identityMatches < min(2, count($identityChecks))) {
            return ['status' => 'mismatch', 'title' => 'Possible changes detected', 'message' => 'Some visible information does not match the protected school record. Do not accept this copy; ask the school to inspect it.'];
        }

        return ['status' => 'match', 'title' => 'Photo matches the protected record', 'message' => 'The readable identity and document entries match the signed school record. The photo was not saved.'];
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
