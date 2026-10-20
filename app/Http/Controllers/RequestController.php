<?php

namespace App\Http\Controllers;

use App\Models\RequestDocument;
use App\Models\RequestDocumentClaim;
use App\Models\RequestDocumentTransmission;
use App\Models\RequestType;
use App\Models\Student;
use App\Models\User;
use App\Support\RequestCatalog;
use App\Support\RequestNotificationService;
use App\Support\RequestStatusTransitions;
use App\Support\SchoolProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RequestController extends Controller
{
    // Records Officer / Registrar View
    public function index(Request $request, RequestStatusTransitions $transitions)
    {
        $filterOptions = [
            'statuses' => ['pending', 'processing', 'processed', 'ready_to_release', 'ready_for_transmission', 'transmitted', 'ready_for_pickup', 'claimed', 'completed', 'rejected'],
            'document_types' => array_values(array_unique([...array_keys(RequestCatalog::BUILT_INS), ...RequestType::withTrashed()->pluck('name')->all(), ...RequestDocument::distinct()->pluck('document_type')->all()])),
            // Keep the legacy value filterable for historical requests, but do not accept it for new requests.
            'payment_methods' => ['cash'],
            'delivery_methods' => ['pickup', 'delivery'],
            'school_years' => config('academics.school_years', []),
        ];

        $filterKeys = ['search', 'status', 'document_type', 'payment_method', 'delivery_method', 'school_year'];
        $request->merge(collect([...$filterKeys, 'sort'])->mapWithKeys(function (string $key) use ($request) {
            $value = $request->query($key);

            return [$key => is_string($value) ? trim($value) : $value];
        })->all());

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', Rule::in($filterOptions['statuses'])],
            'document_type' => ['nullable', 'string', Rule::in($filterOptions['document_types'])],
            'payment_method' => ['nullable', 'string', Rule::in($filterOptions['payment_methods'])],
            'delivery_method' => ['nullable', 'string', Rule::in($filterOptions['delivery_methods'])],
            'school_year' => ['nullable', 'string', Rule::in($filterOptions['school_years'])],
            'sort' => ['nullable', 'string', Rule::in(['newest', 'oldest'])],
        ]);
        $search = $validated['search'] ?? '';
        $sort = $validated['sort'] ?? 'newest';
        $filters = collect(array_diff($filterKeys, ['search']))
            ->mapWithKeys(fn (string $key) => [$key => $validated[$key] ?? ''])
            ->all();

        $user = auth()->user();
        $query = RequestDocument::with([
            'student',
            'requestType',
            'statusHistories.changedBy',
            'authenticities.artifacts',
            'transmissions.transmitter',
            'claim.releaser',
            'manualPreparer',
        ]);

        if ($user->role === 'registrar') {
            // The Registrar reviews prepared requests only. Preparation,
            // release, claiming, and transmission remain staff operations.
            $query->where('status', 'processed');
        } elseif ($user->role === 'admin') {
            // Admin sees all active requests
            $query->whereNotIn('status', ['claimed', 'completed', 'rejected']);
        } else {
            // Records officer sees everything for management
            $query->whereNotIn('status', ['claimed', 'completed', 'rejected']);
        }

        if ($search !== '') {
            $searchPattern = '%'.mb_strtolower($search).'%';

            $query->where(function ($query) use ($searchPattern) {
                $query->whereRaw('LOWER(ticket_number) LIKE ?', [$searchPattern])
                    ->orWhereRaw('LOWER(student_number) LIKE ?', [$searchPattern])
                    ->orWhereHas('student', function ($studentQuery) use ($searchPattern) {
                        $studentQuery->whereRaw('LOWER(name) LIKE ?', [$searchPattern])
                            ->orWhereRaw('LOWER(student_number) LIKE ?', [$searchPattern])
                            ->orWhereRaw('LOWER(lrn) LIKE ?', [$searchPattern]);
                    });
            });
        }

        foreach ($filters as $column => $value) {
            if ($value !== '') {
                $query->where($column, $value);
            }
        }

        $activeParameters = array_filter(
            ['search' => $search, ...$filters],
            fn ($value) => $value !== '',
        );
        $direction = $sort === 'oldest' ? 'asc' : 'desc';
        $paginationParameters = $sort === 'oldest' ? [...$activeParameters, 'sort' => 'oldest'] : $activeParameters;
        $requests = $query->orderBy('created_at', $direction)->orderBy('id', $direction)
            ->paginate(15)->appends($paginationParameters);

        return view('requests.index', compact('requests', 'search', 'sort', 'filters', 'filterOptions', 'activeParameters', 'transitions'));
    }

    public function history()
    {
        $query = RequestDocument::with([
            'student',
            'requestType',
            'authenticities' => fn ($query) => $query->latest('id'),
            'statusHistories.changedBy',
            'transmissions.transmitter',
            'claim.releaser',
            'manualPreparer',
        ]);

        if (auth()->user()?->role === 'registrar') {
            // A Registrar-reviewed request must remain visible after it leaves
            // the approval queue, even while Records staff are still releasing,
            // transmitting, or recording the student's claim.
            $query->where(function ($history) {
                $history->whereIn('status', ['claimed', 'completed', 'rejected'])
                    ->orWhereNotNull('reviewed_at')
                    ->orWhereIn('review_status', ['approved', 'rejected']);
            });
        } else {
            $query->whereIn('status', ['claimed', 'completed', 'rejected']);
        }

        $requests = $query->latest('updated_at')->latest('id')->get();

        return view('requests.history', compact('requests'));
    }

    // Student View
    public function studentIndex()
    {
        $user = auth()->user();
        $studentNumber = session('student_number') ?? ($user ? $user->student_number : null);

        $activeRequests = [];
        $requestHistory = [];

        if ($studentNumber) {
            $activeRequests = RequestDocument::with(['publicStatusHistories', 'transmissions'])
                ->where('student_number', $studentNumber)
                ->whereIn('status', ['pending', 'processing', 'processed', 'ready_to_release', 'ready_for_transmission', 'transmitted', 'ready_for_pickup'])
                ->latest()
                ->get();

            $requestHistory = RequestDocument::with(['publicStatusHistories', 'transmissions'])
                ->where('student_number', $studentNumber)
                ->whereIn('status', ['claimed', 'completed', 'rejected'])
                ->latest()
                ->get();
        }

        $documentPrices = RequestCatalog::availablePrices();
        $dynamicTypes = RequestType::where('is_active', true)->orderBy('name')->get();
        foreach ($dynamicTypes as $type) {
            $documentPrices[$type->name] = (float) $type->fee;
        }

        $schoolProfile = app(SchoolProfile::class)->values();
        $activeRequestLimits = collect($activeRequests)
            ->map(fn (RequestDocument $document) => $this->requestLimitKey(
                $document->document_type,
                $document->school_level,
                $document->request_purpose,
                $document->request_type_id,
            ))
            ->filter()
            ->unique()
            ->values();
        $canBypassRequestLimit = (bool) ($user?->can_bypass_request_limit ?? false);

        return view('requests.student', compact('activeRequests', 'requestHistory', 'studentNumber', 'documentPrices', 'dynamicTypes', 'schoolProfile', 'activeRequestLimits', 'canBypassRequestLimit'));
    }

    public function myRequests()
    {
        $user = auth()->user();
        $studentNumber = session('student_number') ?? ($user ? $user->student_number : null);

        $activeRequests = [];
        $requestHistory = [];

        if ($studentNumber) {
            $activeRequests = RequestDocument::with(['publicStatusHistories', 'transmissions'])
                ->where('student_number', $studentNumber)
                ->whereIn('status', ['pending', 'processing', 'processed', 'ready_to_release', 'ready_for_transmission', 'transmitted', 'ready_for_pickup'])
                ->latest()
                ->get();

            $requestHistory = RequestDocument::with(['publicStatusHistories', 'transmissions'])
                ->where('student_number', $studentNumber)
                ->whereIn('status', ['claimed', 'completed', 'rejected'])
                ->latest()
                ->get();
        }

        return view('requests.my-requests', compact('activeRequests', 'requestHistory', 'studentNumber'));
    }

    public function store(Request $request, RequestNotificationService $notifications)
    {
        $user = auth()->user();
        $request->validate(['document_type' => ['required', 'string', 'max:160']]);
        $type = RequestType::where('is_active', true)->where('name', $request->input('document_type'))->first();
        $isF137 = in_array(strtolower((string) $request->input('document_type')), ['form 137', 'f137'], true);
        $pickupOnly = in_array(strtolower((string) $request->input('document_type')), ['form 138', 'f138', 'diploma'], true);
        $isTransfer = $isF137 && $request->input('request_purpose') === 'transfer_enrollment';
        $isEmploymentRequest = $isF137 && $request->input('request_purpose') === 'employment';

        // Accept any request form that was already open before the address UI was simplified.
        if ($isTransfer && blank($request->input('receiving_school_address'))) {
            $legacyParts = array_filter([
                $request->input('receiving_school_street'),
                $request->input('receiving_barangay'),
                $request->input('receiving_city'),
                $request->input('receiving_province'),
            ]);
            $legacyAddress = $request->input('receiving_address_scope') === 'overseas'
                ? $request->input('receiving_overseas_address')
                : ($legacyParts === [] ? null : implode(', ', [...$legacyParts, 'Philippines']));
            $request->merge(['receiving_school_address' => $legacyAddress]);
        }
        if ($isTransfer && $request->input('receiving_address_scope') === 'domestic' && ! in_array($request->input('receiving_region_code'), ['13', '04', '07'], true)) {
            $prefix = substr((string) $request->input('receiving_region_code'), 0, 2);
            $request->merge(['receiving_region_code' => match (true) {
                $prefix === '13' => '13',
                in_array($prefix, ['01', '02', '03', '04', '05', '14', '17'], true) => '04',
                default => '07',
            }]);
        }

        // Validation changes based on user role and payment method
        $rules = [
            'document_type' => ['required', 'string', Rule::in([...array_keys(RequestCatalog::availablePrices()), ...($type ? [$type->name] : [])])],
            'school_year' => [Rule::excludeIf(! in_array(strtolower((string) $request->input('document_type')), ['form 138', 'f138'], true)), 'required', Rule::in(config('academics.school_years', []))],
            'school_level' => [Rule::excludeIf(! $isF137), 'required', Rule::in(['kinder', 'elementary', 'jhs', 'shs'])],
            'request_purpose' => [Rule::excludeIf(! $isF137), 'required', 'string', Rule::in(['transfer_enrollment', 'employment', 'other'])],
            'purpose_details' => [Rule::excludeIf(! $isF137), Rule::requiredIf(in_array($request->input('request_purpose'), ['transfer_enrollment', 'other'], true)), 'nullable', 'string', 'max:1000'],
            'receiving_school_name' => [Rule::excludeIf(! $isTransfer), 'required', 'string', 'max:160'],
            'receiving_address_scope' => [Rule::excludeIf(! $isTransfer), 'required', Rule::in(['domestic', 'overseas'])],
            'receiving_school_address' => [Rule::excludeIf(! $isTransfer), 'required', 'string', 'max:1000'],
            'receiving_region_code' => [Rule::excludeIf(! $isTransfer), 'nullable', Rule::in(['13', '04', '07'])],
            'request_proof' => [Rule::excludeIf(! $isTransfer), 'required', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:5120'],
            'employer_name' => [Rule::excludeIf(! $isEmploymentRequest), 'required', 'string', 'max:160'],
            'employment_proof' => [Rule::excludeIf(! $isEmploymentRequest), 'required', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:5120'],
            'delivery_method' => ['required', 'string', Rule::in($isTransfer ? ['school_to_school'] : ($pickupOnly ? ['pickup'] : ['pickup', 'delivery']))],
            'payment_method' => [Rule::excludeIf($isTransfer), 'required', 'string', Rule::in(['cash'])],
            'release_location' => [Rule::excludeIf($request->input('delivery_method') !== 'delivery'), 'required', 'string', 'max:1000'],
        ];

        // A school ID is the temporary identity proof for every document request.
        // transcript_receipt remains accepted for forms opened before this change.
        $rules['school_id_proof'] = ['required_without:transcript_receipt', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:5120'];
        $rules['transcript_receipt'] = ['nullable', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:5120'];

        if (! $user || $user->role !== 'student') {
            $rules['student_number'] = 'required|string';
            $rules['name'] = 'required|string';
        }

        $request->validate($rules);

        $studentNumber = $request->student_number;
        $studentName = $request->name;

        // If logged in as student, prioritize their profile info
        if ($user && $user->role === 'student') {
            $studentNumber = $user->student_number;
            $studentName = $user->display_name;
        }

        // Save to session for redundancy/guest requests
        session(['student_number' => $studentNumber]);
        session(['student_name' => $studentName]);

        // Ensure student record exists in students table
        Student::firstOrCreate(
            ['student_number' => $studentNumber],
            ['name' => $studentName]
        );

        $hasReleasedF137 = $isF137 && RequestDocument::where('student_number', $studentNumber)
            ->whereIn('document_type', ['Form 137', 'F137'])
            ->where('status', 'completed')
            ->exists();
        $requiresRegistrarReview = $isF137 && ($request->request_purpose !== 'transfer_enrollment' || $hasReleasedF137);

        // Check for duplicate active requests
        $existingRequest = RequestDocument::where('student_number', $studentNumber)
            ->when($type, fn ($query) => $query->where('request_type_id', $type->id), fn ($query) => $query->where('document_type', $request->document_type))
            ->when($isF137, function ($query) use ($request) {
                if ($request->request_purpose === 'employment') {
                    $query->where('request_purpose', 'employment');

                    return;
                }

                $levels = in_array($request->school_level, ['kinder', 'elementary'], true)
                    ? ['kinder', 'elementary']
                    : [$request->school_level];
                $query->whereIn('school_level', $levels)
                    ->where(fn ($purpose) => $purpose->whereNull('request_purpose')->orWhere('request_purpose', '!=', 'employment'));
            })
            ->whereNotIn('status', ['claimed', 'completed', 'rejected'])
            ->first();

        // Check if user can bypass limit
        $canBypass = false;
        if ($user && $user->role === 'student') {
            $canBypass = $user->can_bypass_request_limit;
        } else {
            // If submitted by staff for a student, find the user record for that student number
            $studentUser = User::where('student_number', $studentNumber)->first();
            if ($studentUser) {
                $canBypass = $studentUser->can_bypass_request_limit;
            }
        }

        if ($existingRequest && ! $canBypass) {
            return redirect()->back()->with('error', "You already have an active request for {$request->document_type}. You need to go to registrar's office to complete your request if you need another copy.");
        }

        $clearanceStatus = null;

        // Generate Ticket Number: REQ-YYYY-XXXX (where XXXX is a unique random string or increment)
        $ticketNumber = 'REQ-'.date('Y').'-'.strtoupper(bin2hex(random_bytes(3)));

        // Ensure uniqueness
        while (RequestDocument::where('ticket_number', $ticketNumber)->exists()) {
            $ticketNumber = 'REQ-'.date('Y').'-'.strtoupper(bin2hex(random_bytes(3)));
        }

        $receipt = $request->file('school_id_proof') ?? $request->file('transcript_receipt');
        $transcriptReceiptPath = $receipt?->storeAs(
            'school_id_proofs/'.now()->format('Y/m'),
            Str::uuid().'.'.$receipt->extension(),
            'local'
        );

        $employmentProof = $request->file('employment_proof');
        $employmentProofPath = $employmentProof?->storeAs(
            'employment_proofs/'.now()->format('Y/m'),
            Str::uuid().'.'.$employmentProof->extension(),
            'local'
        );

        $requestProof = $request->file('request_proof');
        $requestProofPath = $requestProof?->storeAs(
            'request-proofs/'.now()->format('Y/m'),
            Str::uuid().'.'.$requestProof->extension(),
            'local'
        );

        $documentPrice = $type ? (float) $type->fee : $this->documentPrices()[$request->document_type];
        $receivingSchoolAddress = null;
        $lbcSaktoRate = null;
        if ($isTransfer) {
            $receivingSchoolAddress = trim((string) $request->receiving_school_address);
            // Shipping fees are temporarily disabled. Only the document fee is charged.
            $lbcSaktoRate = null;
        }

        $storedPaths = array_filter([$transcriptReceiptPath, $employmentProofPath, $requestProofPath]);
        try {
            $createdRequest = DB::transaction(function () use ($request, $type, $ticketNumber, $studentNumber, $documentPrice, $transcriptReceiptPath, $receipt, $clearanceStatus, $isF137, $isTransfer, $isEmploymentRequest, $hasReleasedF137, $requiresRegistrarReview, $employmentProof, $employmentProofPath, $requestProof, $requestProofPath, $receivingSchoolAddress, $lbcSaktoRate): RequestDocument {
                if ($type) {
                    $current = RequestType::lockForUpdate()->find($type->id);
                    if (! $current || ! $current->is_active || $current->version !== $type->version) {
                        throw ValidationException::withMessages(['document_type' => 'This form changed or became unavailable. Reload the page before submitting.']);
                    }
                }

                return RequestDocument::create([
                    'request_type_id' => $type?->id,
                    'form_snapshot' => $type ? $type->only(['name', 'fee', 'version']) : null,
                    'dynamic_values' => null,
                    'ticket_number' => $ticketNumber,
                    'student_number' => $studentNumber,
                    'document_type' => $request->document_type,
                    'school_year' => $request->document_type === 'Form 138' ? $request->school_year : null,
                    'school_level' => $request->document_type === 'Form 137' ? $request->school_level : null,
                    'request_purpose' => $isF137 ? $request->request_purpose : null,
                    'purpose_details' => $isF137 ? $request->purpose_details : null,
                    'receiving_school_name' => $isTransfer ? $request->receiving_school_name : null,
                    'receiving_school_address' => $receivingSchoolAddress,
                    'receiving_address_scope' => $isTransfer ? $request->receiving_address_scope : null,
                    'receiving_school_street' => null,
                    'receiving_barangay' => null,
                    'receiving_city' => null,
                    'receiving_province' => null,
                    'receiving_region_code' => $isTransfer && $request->receiving_address_scope === 'domestic' ? $request->receiving_region_code : null,
                    'receiving_overseas_address' => $isTransfer && $request->receiving_address_scope === 'overseas' ? $receivingSchoolAddress : null,
                    'lbc_sakto_rate' => $lbcSaktoRate,
                    'request_proof_path' => $requestProofPath,
                    'request_proof_original_name' => $requestProof?->getClientOriginalName(),
                    'request_proof_mime_type' => $requestProof?->getMimeType(),
                    'request_proof_size' => $requestProof?->getSize(),
                    'employer_name' => $isEmploymentRequest ? $request->employer_name : null,
                    'employment_proof_path' => $employmentProofPath,
                    'employment_proof_original_name' => $employmentProof?->getClientOriginalName(),
                    'employment_proof_mime_type' => $employmentProof?->getMimeType(),
                    'employment_proof_size' => $employmentProof?->getSize(),
                    'is_f137_employment_exception' => $hasReleasedF137 && $isEmploymentRequest,
                    'review_status' => $requiresRegistrarReview ? 'pending' : null,
                    'document_price' => $documentPrice,
                    'delivery_method' => $request->delivery_method,
                    'payment_method' => $isTransfer ? 'cash' : $request->payment_method,
                    'release_location' => $request->delivery_method === 'delivery' ? $request->release_location : null,
                    'pickup_location' => $request->document_type === 'Form 138'
                        ? implode(' — ', array_filter([app(SchoolProfile::class)->values()['school'], app(SchoolProfile::class)->values()['address'] ?: "Registrar's Office"]))
                        : null,
                    'payment_proof_path' => $transcriptReceiptPath,
                    'payment_proof_disk' => 'local',
                    'payment_proof_original_name' => $receipt?->getClientOriginalName(),
                    'payment_proof_mime_type' => $receipt?->getMimeType(),
                    'payment_proof_size' => $receipt?->getSize(),
                    'payment_proof_sha256' => $receipt ? hash_file('sha256', $receipt->getRealPath()) : null,
                    'clearance_status' => $clearanceStatus,
                    'payment_confirmed' => false,
                    'status' => 'pending',
                ]);
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($storedPaths);
            throw $exception;
        }

        $notifications->statusChanged($createdRequest);

        if ($receipt) {
            record_log(
                'Uploaded School ID Proof',
                'File Security',
                "Uploaded school ID proof for request #{$createdRequest->id}; student: {$studentNumber}; MIME: {$createdRequest->payment_proof_mime_type}; size: {$createdRequest->payment_proof_size} bytes; role: ".($user?->role ?? 'unknown')
            );
        }

        // Reset the bypass flag after one successful bypass
        if ($canBypass) {
            if ($user && $user->role === 'student') {
                $user->update(['can_bypass_request_limit' => false]);
            } else {
                $studentUser = User::where('student_number', $studentNumber)->first();
                if ($studentUser) {
                    $studentUser->update(['can_bypass_request_limit' => false]);
                }
            }
        }

        $storedPaymentMethod = $isTransfer ? 'cash_after_assessment' : $request->payment_method;
        record_log('Submitted Request', 'Requests', "Student #{$studentNumber} requested {$request->document_type} for ₱".number_format($documentPrice, 2)." (Ticket: {$ticketNumber}) - Delivery: {$request->delivery_method}, Payment: {$storedPaymentMethod}");

        $message = "Your request has been submitted! Ticket Number: {$ticketNumber}. Document fee: ₱".number_format($documentPrice, 2).'.';
        $message .= $isTransfer
            ? ' The Records Officer will review the receiving-school address and notify you of the cash amount and delivery method.'
            : ' Your school ID proof will be reviewed with the request. Document payment awaits staff verification.';

        return redirect()->back()->with('success', $message);
    }

    public function receipt(Request $request, RequestDocument $requestDocument)
    {
        $user = $this->authorizeReceiptViewer($request, $requestDocument);
        $requestDocument->loadMissing('student');
        $schoolProfile = app(SchoolProfile::class)->values();

        record_log('Viewed Request Receipt', 'Requests', "Viewed request receipt for #{$requestDocument->id}; role: {$user->role}");

        return response()
            ->view('requests.receipt', compact('requestDocument', 'schoolProfile'))
            ->header('Cache-Control', 'private, no-store, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('X-Content-Type-Options', 'nosniff');
    }

    public function dynamicAttachment(Request $request, RequestDocument $requestDocument, string $field)
    {
        $this->authorizeReceiptViewer($request, $requestDocument);
        $definition = collect($requestDocument->form_snapshot['fields'] ?? [])->firstWhere('key', $field);
        abort_unless(($definition['type'] ?? null) === 'file', 404);
        $file = $requestDocument->dynamic_values[$field] ?? null;
        abort_unless(is_array($file) && str_starts_with($file['path'] ?? '', 'request-attachments/') && ! str_contains($file['path'], '..'), 404);
        abort_unless(Storage::disk('local')->exists($file['path']), 404);

        return Storage::disk('local')->download($file['path'], $file['name'], [
            'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function uploadedReceipt(Request $request, RequestDocument $requestDocument)
    {
        $user = $this->authorizeReceiptViewer($request, $requestDocument);

        if (blank($requestDocument->payment_proof_path)) {
            record_log('School ID Proof Missing', 'File Security', "School ID proof unavailable for request #{$requestDocument->id}", 'missing');
            abort(404);
        }

        // Rows created before private receipt storage used the public disk.
        $disk = $requestDocument->payment_proof_disk ?: 'public';
        if (! in_array($disk, ['local', 'public'], true) || ! Storage::disk($disk)->exists($requestDocument->payment_proof_path)) {
            record_log('School ID Proof Missing', 'File Security', "School ID proof unavailable for request #{$requestDocument->id}", 'missing');
            abort(404);
        }

        record_log(
            'Viewed School ID Proof',
            'File Security',
            "Viewed school ID proof for request #{$requestDocument->id}; student: {$requestDocument->student_number}; role: {$user->role}"
        );

        $downloadName = $requestDocument->payment_proof_original_name
            ?: basename($requestDocument->payment_proof_path);

        return Storage::disk($disk)->response(
            $requestDocument->payment_proof_path,
            $downloadName,
            [
                'Cache-Control' => 'private, no-store, max-age=0',
                'Pragma' => 'no-cache',
                'X-Content-Type-Options' => 'nosniff',
                'Content-Security-Policy' => "default-src 'none'; sandbox",
            ]
        );
    }

    public function employmentProof(Request $request, RequestDocument $requestDocument)
    {
        abort_unless(in_array($request->user()?->role, ['admin', 'registrar', 'records_officer'], true), 403);
        abort_unless(filled($requestDocument->employment_proof_path), 404);
        abort_unless(
            str_starts_with($requestDocument->employment_proof_path, 'employment_proofs/')
                && ! str_contains($requestDocument->employment_proof_path, '..')
                && Storage::disk('local')->exists($requestDocument->employment_proof_path),
            404
        );

        record_log('Viewed Employment Proof', 'File Security', "Viewed employment proof for request #{$requestDocument->id}; role: {$request->user()->role}");

        return Storage::disk('local')->response(
            $requestDocument->employment_proof_path,
            $requestDocument->employment_proof_original_name ?: basename($requestDocument->employment_proof_path),
            [
                'Cache-Control' => 'private, no-store, max-age=0',
                'Pragma' => 'no-cache',
                'X-Content-Type-Options' => 'nosniff',
                'Content-Security-Policy' => "default-src 'none'; sandbox",
            ]
        );
    }

    public function requestProof(Request $request, RequestDocument $requestDocument)
    {
        abort_unless(in_array($request->user()?->role, ['admin', 'registrar', 'records_officer'], true), 403);
        abort_unless(filled($requestDocument->request_proof_path), 404);
        abort_unless(str_starts_with($requestDocument->request_proof_path, 'request-proofs/')
            && ! str_contains($requestDocument->request_proof_path, '..')
            && Storage::disk('local')->exists($requestDocument->request_proof_path), 404);

        record_log('Viewed F137 Request Proof', 'File Security', "Viewed request proof for request #{$requestDocument->id}; role: {$request->user()->role}");

        return Storage::disk('local')->response($requestDocument->request_proof_path,
            $requestDocument->request_proof_original_name ?: basename($requestDocument->request_proof_path), [
                'Cache-Control' => 'private, no-store, max-age=0',
                'X-Content-Type-Options' => 'nosniff',
                'Content-Security-Policy' => "default-src 'none'; sandbox",
            ]);
    }

    public function documentQuickView(Request $request, RequestDocument $requestDocument)
    {
        abort_unless(in_array($request->user()?->role, ['admin', 'registrar', 'records_officer'], true), 403);

        [$document, $artifact] = $this->preparedDocumentArtifact($requestDocument);

        record_log('Reviewed Generated Document', 'Document Review', "Opened signed document {$document->control_number} for request #{$requestDocument->id}");

        return Storage::disk('local')->response(
            $artifact->storage_path,
            $artifact->original_filename ?: $document->control_number.'.pdf',
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.str_replace(['"', '/', '\\'], '-', $artifact->original_filename ?: $document->control_number.'.pdf').'"',
                'Cache-Control' => 'private, no-store, max-age=0',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    public function documentDownload(Request $request, RequestDocument $requestDocument)
    {
        abort_unless(in_array($request->user()?->role, ['admin', 'registrar', 'records_officer'], true), 403);

        [$document, $artifact] = $this->preparedDocumentArtifact($requestDocument);

        record_log('Downloaded Generated Document', 'Document Review', "Downloaded signed document {$document->control_number} for request #{$requestDocument->id}");

        return Storage::disk('local')->download(
            $artifact->storage_path,
            $artifact->original_filename ?: $document->control_number.'.pdf',
            ['Cache-Control' => 'private, no-store, max-age=0', 'X-Content-Type-Options' => 'nosniff']
        );
    }

    private function preparedDocumentArtifact(RequestDocument $requestDocument): array
    {
        $document = $requestDocument->authenticities()
            ->where('status', 'valid')
            ->where('pdf_signature_status', 'signed')
            ->latest('id')
            ->firstOrFail();
        $artifact = $document->artifacts()
            ->where('is_pdf_signed', true)
            ->whereNotNull('storage_path')
            ->latest('id')
            ->firstOrFail();

        abort_unless($artifact->storage_disk === 'local', 404);
        abort_unless(
            (str_starts_with($artifact->storage_path, 'review-documents/') || str_starts_with($artifact->storage_path, 'issued-documents/'))
                && ! str_contains($artifact->storage_path, '..'),
            404
        );
        abort_unless(Storage::disk('local')->exists($artifact->storage_path), 404);

        return [$document, $artifact];
    }

    private function authorizeReceiptViewer(Request $request, RequestDocument $requestDocument): User
    {
        $user = $request->user();
        $isOwner = $user?->role === 'student'
            && filled($user->student_number)
            && hash_equals((string) $requestDocument->student_number, (string) $user->student_number);
        $isStaff = in_array($user?->role, ['admin', 'registrar', 'records_officer'], true);

        if (! $isOwner && ! $isStaff) {
            record_log(
                'Receipt Access Denied',
                'File Security',
                "Denied receipt access for request #{$requestDocument->id}; role: ".($user?->role ?? 'unknown'),
                'denied'
            );
            abort(403);
        }

        return $user;
    }

    private function requestLimitKey(string $documentType, ?string $schoolLevel = null, ?string $requestPurpose = null, ?int $requestTypeId = null): string
    {
        if ($requestTypeId) {
            return 'custom:'.$requestTypeId;
        }

        if (in_array(strtolower($documentType), ['form 137', 'f137'], true)) {
            if ($requestPurpose === 'employment') {
                return 'f137:employment';
            }

            $level = in_array($schoolLevel, ['kinder', 'elementary'], true) ? 'elementary' : $schoolLevel;

            return $level ? 'f137:'.$level : '';
        }

        return 'document:'.strtolower($documentType);
    }

    private function documentPrices(): array
    {
        return RequestCatalog::prices();
    }

    public function confirmPayment(Request $request, $request_id)
    {
        $user = auth()->user();

        abort_unless(in_array($user?->role, ['registrar', 'admin', 'records_officer'], true), 403);

        $validated = $request->validate([
            'payment_amount' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'official_receipt_number' => ['nullable', 'string', 'max:100'],
        ]);

        $requestDoc = DB::transaction(function () use ($request_id, $user, $validated): RequestDocument {
            $requestDoc = RequestDocument::query()->lockForUpdate()->findOrFail($request_id);
            abort_if(in_array($requestDoc->status, ['claimed', 'completed', 'rejected'], true), 422);
            if ($user->role === 'records_officer') {
                abort_unless(
                    $requestDoc->document_type === 'Form 137'
                    && $requestDoc->request_purpose === 'transfer_enrollment'
                    && $requestDoc->delivery_assessed_at,
                    403
                );
            }

            if ($requestDoc->document_type === 'Form 137' && $requestDoc->request_purpose === 'transfer_enrollment') {
                abort_unless($requestDoc->delivery_assessed_at && $requestDoc->f137_delivery_method, 422, 'Assess the F137 delivery method and fee before confirming payment.');
                $expected = (float) $requestDoc->document_price + (float) $requestDoc->delivery_fee;
                $amount = array_key_exists('payment_amount', $validated) && $validated['payment_amount'] !== null
                    ? (float) $validated['payment_amount'] : $expected;
                abort_if(abs($amount - $expected) > 0.009, 422, 'The cash payment must match the assessed total amount.');
            }

            if (! $requestDoc->payment_confirmed) {
                $paymentUpdate = [
                    'payment_confirmed' => true,
                    'payment_confirmed_at' => now(),
                    'payment_confirmed_by' => $user->id,
                    'payment_amount' => $validated['payment_amount'] ?? ((float) $requestDoc->document_price + (float) $requestDoc->delivery_fee),
                    'official_receipt_number' => $validated['official_receipt_number'] ?? null,
                ];
                if ($requestDoc->document_type === 'Form 137' && $requestDoc->request_purpose === 'transfer_enrollment') {
                    $paymentUpdate['remarks'] = 'Cash payment received by the Records Officer; ready for shipping or school delivery.';
                }
                $requestDoc->update($paymentUpdate);
            }

            return $requestDoc;
        });

        record_log('Payment Confirmed', 'Requests', "Confirmed payment for Request #{$request_id} (Ticket: {$requestDoc->ticket_number})");

        return redirect()->back()->with('success', 'Cash payment recorded. The document is now ready for shipping or delivery.');
    }

    public function assessF137Delivery(Request $request, $request_id, RequestNotificationService $notifications)
    {
        abort_unless($request->user()?->role === 'records_officer', 403);
        $validated = $request->validate([
            'f137_delivery_method' => ['required', Rule::in(['lbc', 'staff_delivery'])],
            'delivery_fee' => ['required', 'numeric', 'min:0', 'max:999999.99'],
        ]);

        $requestDoc = DB::transaction(function () use ($request_id, $request, $validated): RequestDocument {
            $document = RequestDocument::query()->lockForUpdate()->findOrFail($request_id);
            abort_unless($document->document_type === 'Form 137' && $document->request_purpose === 'transfer_enrollment', 422);
            abort_unless($document->status === 'ready_for_transmission', 422, 'Delivery can be assessed only after Registrar approval.');
            abort_if($document->payment_confirmed, 422, 'Delivery assessment cannot be changed after payment confirmation.');

            $document->update([
                'f137_delivery_method' => $validated['f137_delivery_method'],
                'delivery_fee' => $validated['delivery_fee'],
                'delivery_assessed_at' => now(),
                'delivery_assessed_by' => $request->user()->id,
                'remarks' => 'Amount sent to student; waiting for cash payment to the Records Officer.',
            ]);

            return $document;
        });

        $notifications->deliveryAssessed($requestDoc);
        record_log('F137 Delivery Assessed', 'Requests', "Assessed delivery for Request #{$requestDoc->id}: {$requestDoc->f137_delivery_method}, ₱{$requestDoc->delivery_fee}");

        return redirect()->route('requests.index', ['open_request' => $requestDoc->id])
            ->with('success', 'Estimated delivery fee saved. The student has been notified of the total amount due to the Records Officer.');
    }

    public function updateClearance(Request $request, $request_id)
    {
        $user = auth()->user();

        abort_unless(in_array($user?->role, ['registrar', 'admin'], true), 403);

        $request->validate([
            'clearance_status' => 'required|string|in:cleared,pending_clearance',
        ]);

        $requestDoc = RequestDocument::findOrFail($request_id);

        $requestDoc->update([
            'clearance_status' => $request->clearance_status,
        ]);

        record_log('Clearance Updated', 'Requests', "Updated clearance for Request #{$request_id} to {$request->clearance_status}");

        return redirect()->back()->with('success', 'Clearance status updated successfully.');
    }

    public function updateStatus(Request $request, $request_id, RequestStatusTransitions $transitions, RequestNotificationService $notifications)
    {
        $user = auth()->user();
        abort_unless(in_array($user?->role, ['registrar', 'admin', 'records_officer'], true), 403);

        $request->validate([
            'status' => 'required|string|in:pending,processing,processed,ready_to_release,ready_for_transmission,transmitted,ready_for_pickup,claimed,completed,rejected',
            'remarks' => 'required_if:status,rejected|nullable|string|max:1000',
            'transmission_method' => ['required_if:status,transmitted', 'nullable', Rule::in(['lbc', 'staff_delivery'])],
            'recipient_name' => 'required_if:status,transmitted|nullable|string|max:160',
            'transmission_destination' => 'required_if:status,transmitted|nullable|string|max:1000',
            'reference_number' => [Rule::requiredIf(fn () => $request->input('status') === 'transmitted' && $request->input('transmission_method') === 'lbc'), 'nullable', 'string', 'max:160'],
            'transmission_proof' => [Rule::requiredIf(fn () => $request->input('status') === 'transmitted' && $request->input('transmission_method') === 'lbc'), 'nullable', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:5120'],
            'manual_preparation' => ['nullable', 'boolean'],
            'manual_preparation_reason' => [Rule::requiredIf(fn () => $request->boolean('manual_preparation')), 'nullable', 'string', 'max:1000'],
        ]);

        $transmissionProof = $request->file('transmission_proof');
        $transmissionProofPath = $transmissionProof?->storeAs('transmission-proofs/'.now()->format('Y/m'), Str::uuid().'.'.$transmissionProof->extension(), 'local');

        try {
            $requestDoc = DB::transaction(function () use ($request, $request_id, $user, $transitions, $transmissionProof, $transmissionProofPath): RequestDocument {
                $lockedRequest = RequestDocument::query()->lockForUpdate()->findOrFail($request_id);

                // A document maker can be opened directly from a new request.
                // When the protected document is already prepared, one Forward
                // action records the required Processing step before forwarding.
                if (
                    ! $request->boolean('manual_preparation')
                    && $user->role === 'records_officer'
                    && $lockedRequest->request_type_id === null
                    && $lockedRequest->status === 'pending'
                    && $request->status === 'processed'
                    && $lockedRequest->hasPreparedDocument()
                ) {
                    $lockedRequest->update([
                        'status' => 'processing',
                        'remarks' => 'Records Officer completed document preparation and started processing.',
                    ]);
                }

                if ($request->boolean('manual_preparation')) {
                    abort_unless(
                        $user->role === 'records_officer'
                        && ! str_contains(strtolower($lockedRequest->document_type), 'diploma')
                        && in_array($lockedRequest->status, ['pending', 'processing'], true)
                        && $request->status === 'processed',
                        403
                    );

                    // A manual preparation may be selected directly from a new request.
                    // Preserve the normal audit trail instead of silently skipping Processing.
                    if ($lockedRequest->status === 'pending') {
                        $lockedRequest->update([
                            'status' => 'processing',
                            'remarks' => 'Records Officer started the manual document preparation procedure.',
                        ]);
                    }

                    $lockedRequest->update([
                        'is_manually_prepared' => true,
                        'manual_preparation_reason' => $request->manual_preparation_reason,
                        'manually_prepared_at' => now(),
                        'manually_prepared_by' => $user->id,
                    ]);
                }

                $transitions->validate($lockedRequest, $user, $request->status, $request->remarks);

                $registrarApproval = $user->role === 'registrar'
                    && in_array($request->status, ['ready_to_release', 'ready_for_transmission', 'ready_for_pickup'], true);
                $registrarReview = $registrarApproval || ($user->role === 'registrar' && $request->status === 'rejected');

                $lockedRequest->update([
                    'status' => $request->status,
                    'remarks' => $request->remarks,
                    'exception_approved_at' => $lockedRequest->is_f137_employment_exception && $request->status === 'processing'
                        ? ($lockedRequest->exception_approved_at ?: now())
                        : $lockedRequest->exception_approved_at,
                    'exception_approved_by' => $lockedRequest->is_f137_employment_exception && $request->status === 'processing'
                        ? ($lockedRequest->exception_approved_by ?: $user->id)
                        : $lockedRequest->exception_approved_by,
                    'review_status' => $registrarReview ? ($registrarApproval ? 'approved' : 'rejected') : $lockedRequest->review_status,
                    'reviewed_at' => $registrarReview ? now() : $lockedRequest->reviewed_at,
                    'reviewed_by' => $registrarReview ? $user->id : $lockedRequest->reviewed_by,
                    'review_notes' => $registrarReview ? $request->remarks : $lockedRequest->review_notes,
                    'ready_for_pickup_at' => $request->status === 'ready_for_pickup' ? now() : $lockedRequest->ready_for_pickup_at,
                ]);

                if ($request->status === 'transmitted') {
                    abort_unless($lockedRequest->document_type === 'Form 137' && $lockedRequest->request_purpose === 'transfer_enrollment', 422);
                    abort_unless($lockedRequest->delivery_assessed_at && $lockedRequest->f137_delivery_method, 422, 'Assess the delivery method and fee first.');
                    abort_unless($lockedRequest->payment_confirmed, 422, 'Confirm the student cash payment before transmitting the F137.');
                    abort_unless($request->transmission_method === $lockedRequest->f137_delivery_method, 422, 'The transmission method must match the approved delivery assessment.');
                    RequestDocumentTransmission::create([
                        'request_document_id' => $lockedRequest->id,
                        'method' => $request->transmission_method,
                        'recipient_name' => $request->recipient_name,
                        'destination' => $request->transmission_destination,
                        'reference_number' => $request->reference_number,
                        'transmitted_at' => now(),
                        'transmitted_by' => $user->id,
                        'proof_path' => $transmissionProofPath,
                        'proof_original_name' => $transmissionProof?->getClientOriginalName(),
                        'proof_mime_type' => $transmissionProof?->getMimeType(),
                        'proof_size' => $transmissionProof?->getSize(),
                        'remarks' => $request->remarks,
                    ]);
                }

                if ($request->status === 'claimed') {
                    abort_unless($lockedRequest->document_type === 'Form 138', 422);
                    RequestDocumentClaim::create([
                        'request_document_id' => $lockedRequest->id,
                        'claimed_at' => now(),
                        'released_by' => $user->id,
                        'claimant_name' => $lockedRequest->student->name,
                        'claimant_type' => 'student',
                        'valid_id_type' => 'Not recorded',
                        'valid_id_last_four' => '----',
                        'authorization_path' => null,
                        'authorization_original_name' => null,
                        'remarks' => $request->remarks,
                    ]);
                }

                if ($request->status === 'rejected') {
                    $lockedRequest->authenticities()
                        ->whereIn('status', ['valid', 'superseded'])
                        ->update([
                            'status' => 'revoked',
                            'revoked_at' => now(),
                            'revoked_by' => $user->id,
                            'revocation_reason' => $request->remarks ?: 'The related document request was rejected.',
                        ]);
                }

                return $lockedRequest;
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete(array_filter([$transmissionProofPath]));
            throw $exception;
        }

        if ($requestDoc->wasChanged('status')) {
            $notifications->statusChanged($requestDoc);
        }

        record_log('Updated Request Status', 'Requests', "Updated Request #{$request_id} status to {$request->status}");

        if (
            $request->status === 'processing'
            && ! $requestDoc->request_type_id
            && in_array($user->role, ['records_officer', 'admin'], true)
            && in_array(strtolower($requestDoc->document_type), ['form 137', 'form 138', 'f137', 'f138'], true)
        ) {
            $form = str_contains(strtolower($requestDoc->document_type), '137') ? 'f137' : 'f138';

            return redirect()->route("school-forms.{$form}.preview", array_filter([
                'student' => $requestDoc->student_number,
                'school_year' => $form === 'f138' ? $requestDoc->school_year : null,
                'request_id' => $requestDoc->id,
            ], fn ($value) => $value !== null && $value !== ''));
        }

        return redirect()->route('requests.index', ['open_request' => $requestDoc->id])
            ->with('success', 'Request status updated to '.str_replace('_', ' ', $request->status).'.');
    }

    public function clearHistory(Request $request)
    {
        abort_unless($request->user()?->role === 'admin', 403);

        $validated = $request->validate([
            'action' => 'required|string|in:completed,claimed,rejected,all',
        ]);

        $action = $request->input('action');

        if ($action === 'all') {
            $count = RequestDocument::whereIn('status', ['claimed', 'completed', 'rejected'])->delete();
            record_log('Cleared Request History', 'Requests', "Deleted {$count} request records (all)");
        } elseif ($action === 'completed') {
            $count = RequestDocument::where('status', 'completed')->delete();
            record_log('Cleared Request History', 'Requests', "Deleted {$count} completed request records");
        } elseif ($action === 'rejected') {
            $count = RequestDocument::where('status', 'rejected')->delete();
            record_log('Cleared Request History', 'Requests', "Deleted {$count} rejected request records");
        }

        return redirect()->back()->with('success', 'Request history cleared successfully.');
    }

    public function resetAll(Request $request)
    {
        abort_unless($request->user()?->role === 'admin', 403);

        $request->validate([
            'reset_confirmation' => ['required', 'string', 'in:RESET'],
        ], [
            'reset_confirmation.in' => 'Type RESET to confirm the request system reset.',
        ]);

        $count = DB::transaction(function (): int {
            $count = RequestDocument::count();
            RequestDocument::query()->delete();

            return $count;
        });

        record_log('Full Request System Reset', 'Requests', "Permanently deleted all {$count} request records from the system.");

        return redirect()->back()->with('success', 'System Reset Successful: All requests and history have been cleared.');
    }

    private function lbcSaktoRate(?string $regionCode): float
    {
        if (in_array($regionCode, ['13', '04', '07'], true)) {
            return match ($regionCode) {
                '13' => 39.00,
                '04' => 49.00,
                '07' => 69.00,
            };
        }

        $prefix = substr((string) $regionCode, 0, 2);

        return match (true) {
            $prefix === '13' => 39.00,
            in_array($prefix, ['01', '02', '03', '04', '05', '14', '17'], true) => 49.00,
            default => 69.00,
        };
    }
}
