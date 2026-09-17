<?php

namespace App\Http\Controllers;

use App\Models\RequestDocument;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use App\Support\SchoolProfile;
use App\Support\RequestStatusTransitions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RequestController extends Controller
{
    // Records Officer / Registrar View
    public function index(Request $request, RequestStatusTransitions $transitions)
    {
        $filterOptions = [
            'statuses' => ['pending', 'processing', 'processed', 'ready_to_release', 'completed', 'rejected'],
            'document_types' => ['Form 137', 'Form 138', 'Certificate of Enrollment', 'Certificate of Completion', 'Certificate of Good Moral Character', 'Certificate of Recognition', 'Diploma'],
            'payment_methods' => ['cash', 'gcash', 'bank_transfer'],
            'delivery_methods' => ['pickup', 'delivery'],
            'school_years' => config('academics.school_years', []),
        ];

        $filterKeys = ['search', 'status', 'document_type', 'payment_method', 'delivery_method', 'school_year'];
        $request->merge(collect($filterKeys)->mapWithKeys(function (string $key) use ($request) {
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
        ]);
        $search = $validated['search'] ?? '';
        $filters = collect(array_diff($filterKeys, ['search']))
            ->mapWithKeys(fn (string $key) => [$key => $validated[$key] ?? ''])
            ->all();

        $user = auth()->user();
        $query = RequestDocument::with(['student', 'statusHistories.changedBy']);

        if ($user->role === 'registrar') {
            // Registrar only sees processed requests that need approval
            $query->whereIn('status', ['processed', 'ready_to_release']);
        } elseif ($user->role === 'admin') {
            // Admin sees all active requests
            $query->whereNotIn('status', ['completed', 'rejected']);
        } else {
            // Records officer sees everything for management
            $query->whereNotIn('status', ['completed', 'rejected']);
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
        $requests = $query->latest()->paginate(15)->appends($activeParameters);

        return view('requests.index', compact('requests', 'search', 'filters', 'filterOptions', 'activeParameters', 'transitions'));
    }

    public function history()
    {
        $requests = RequestDocument::with([
            'student',
            'authenticities' => fn ($query) => $query->latest('id'),
            'statusHistories.changedBy',
        ])
            ->whereIn('status', ['completed', 'rejected'])
            ->latest()
            ->get();

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
            $activeRequests = RequestDocument::with('publicStatusHistories')
                ->where('student_number', $studentNumber)
                ->whereIn('status', ['pending', 'processing', 'processed', 'ready_to_release'])
                ->latest()
                ->get();

            $requestHistory = RequestDocument::with('publicStatusHistories')
                ->where('student_number', $studentNumber)
                ->whereIn('status', ['completed', 'rejected'])
                ->latest()
                ->get();
        }

        $documentPrices = $this->documentPrices();

        return view('requests.student', compact('activeRequests', 'requestHistory', 'studentNumber', 'documentPrices'));
    }

    public function myRequests()
    {
        $user = auth()->user();
        $studentNumber = session('student_number') ?? ($user ? $user->student_number : null);

        $activeRequests = [];
        $requestHistory = [];

        if ($studentNumber) {
            $activeRequests = RequestDocument::with('publicStatusHistories')
                ->where('student_number', $studentNumber)
                ->whereIn('status', ['pending', 'processing', 'processed', 'ready_to_release'])
                ->latest()
                ->get();

            $requestHistory = RequestDocument::with('publicStatusHistories')
                ->where('student_number', $studentNumber)
                ->whereIn('status', ['completed', 'rejected'])
                ->latest()
                ->get();
        }

        return view('requests.my-requests', compact('activeRequests', 'requestHistory', 'studentNumber'));
    }

    public function store(Request $request, \App\Support\RequestNotificationService $notifications)
    {
        $user = auth()->user();

        // Validation changes based on user role and payment method
        $rules = [
            'document_type' => 'required|string|in:Form 137,Form 138,Certificate of Enrollment,Certificate of Completion,Certificate of Good Moral Character,Certificate of Recognition,Diploma',
            'school_year' => ['required_if:document_type,Form 138', 'nullable', Rule::in(config('academics.school_years', []))],
            'school_level' => ['required_if:document_type,Form 137', 'nullable', Rule::in(['kinder', 'elementary', 'jhs', 'shs'])],
            'delivery_method' => 'required|string|in:pickup,delivery',
            'payment_method' => 'required|string|in:cash,gcash,bank_transfer',
            'release_location' => 'nullable|string',
        ];

        // Accounting clearance is required before any request can be submitted.
        $rules['transcript_receipt'] = 'required|file|mimes:jpeg,png,jpg,pdf|max:5120';

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

        // Check for duplicate active requests
        $existingRequest = RequestDocument::where('student_number', $studentNumber)
            ->where('document_type', $request->document_type)
            ->whereNotIn('status', ['completed', 'rejected'])
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

        // A receipt upload is evidence to review, not proof of clearance or payment.
        $clearanceStatus = 'pending_clearance';
        $financialBalance = 0.00;

        // Generate Ticket Number: REQ-YYYY-XXXX (where XXXX is a unique random string or increment)
        $ticketNumber = 'REQ-'.date('Y').'-'.strtoupper(bin2hex(random_bytes(3)));

        // Ensure uniqueness
        while (RequestDocument::where('ticket_number', $ticketNumber)->exists()) {
            $ticketNumber = 'REQ-'.date('Y').'-'.strtoupper(bin2hex(random_bytes(3)));
        }

        $receipt = $request->file('transcript_receipt');
        $receiptExtension = $receipt->extension();
        $transcriptReceiptPath = $receipt->storeAs(
            'transcript_receipts/'.now()->format('Y/m'),
            Str::uuid().'.'.$receiptExtension,
            'local'
        );

        $documentPrice = $this->documentPrices()[$request->document_type];

        $createdRequest = DB::transaction(fn (): RequestDocument => RequestDocument::create([
            'ticket_number' => $ticketNumber,
            'student_number' => $studentNumber,
            'document_type' => $request->document_type,
            'school_year' => $request->document_type === 'Form 138' ? $request->school_year : null,
            'school_level' => $request->document_type === 'Form 137' ? $request->school_level : null,
            'document_price' => $documentPrice,
            'delivery_method' => $request->delivery_method,
            'payment_method' => $request->payment_method,
            'release_location' => $request->release_location,
            'payment_proof_path' => $transcriptReceiptPath,
            'payment_proof_disk' => 'local',
            'payment_proof_original_name' => $receipt->getClientOriginalName(),
            'payment_proof_mime_type' => $receipt->getMimeType(),
            'payment_proof_size' => $receipt->getSize(),
            'payment_proof_sha256' => hash_file('sha256', $receipt->getRealPath()),
            'clearance_status' => $clearanceStatus,
            'financial_balance' => $financialBalance,
            'payment_confirmed' => false,
            'status' => 'pending',
        ]));

        $notifications->statusChanged($createdRequest);

        record_log(
            'Uploaded Payment Receipt',
            'File Security',
            "Uploaded receipt for request #{$createdRequest->id}; student: {$studentNumber}; MIME: {$createdRequest->payment_proof_mime_type}; size: {$createdRequest->payment_proof_size} bytes; role: ".($user?->role ?? 'unknown')
        );

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

        record_log('Submitted Request', 'Requests', "Student #{$studentNumber} requested {$request->document_type} for ₱".number_format($documentPrice, 2)." (Ticket: {$ticketNumber}) - Delivery: {$request->delivery_method}, Payment: {$request->payment_method}");

        $message = "Your request has been submitted! Ticket Number: {$ticketNumber}. Document fee: ₱".number_format($documentPrice, 2).'.';
        $message .= ' Accounting clearance and document payment await staff verification.';

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

    public function uploadedReceipt(Request $request, RequestDocument $requestDocument)
    {
        $user = $this->authorizeReceiptViewer($request, $requestDocument);

        if (blank($requestDocument->payment_proof_path)) {
            record_log('Receipt File Missing', 'File Security', "Receipt unavailable for request #{$requestDocument->id}", 'missing');
            abort(404);
        }

        // Rows created before private receipt storage used the public disk.
        $disk = $requestDocument->payment_proof_disk ?: 'public';
        if (! in_array($disk, ['local', 'public'], true) || ! Storage::disk($disk)->exists($requestDocument->payment_proof_path)) {
            record_log('Receipt File Missing', 'File Security', "Receipt unavailable for request #{$requestDocument->id}", 'missing');
            abort(404);
        }

        record_log(
            'Viewed Payment Receipt',
            'File Security',
            "Viewed receipt for request #{$requestDocument->id}; student: {$requestDocument->student_number}; role: {$user->role}"
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

    private function documentPrices(): array
    {
        $defaults = [
            'Form 137' => 150,
            'Form 138' => 100,
            'Certificate of Enrollment' => 100,
            'Certificate of Completion' => 120,
            'Certificate of Good Moral Character' => 100,
            'Certificate of Recognition' => 120,
            'Diploma' => 150,
        ];

        $keys = [
            'Form 137' => 'price_form_137',
            'Form 138' => 'price_form_138',
            'Certificate of Enrollment' => 'price_certificate_enrollment',
            'Certificate of Completion' => 'price_certificate_completion',
            'Certificate of Good Moral Character' => 'price_good_moral',
            'Certificate of Recognition' => 'price_certificate_recognition',
            'Diploma' => 'price_diploma',
        ];

        $settings = Setting::whereIn('key', array_values($keys))->pluck('value', 'key');

        foreach ($keys as $document => $key) {
            $defaults[$document] = (float) ($settings[$key] ?? $defaults[$document]);
        }

        return $defaults;
    }

    public function confirmPayment(Request $request, $request_id)
    {
        $user = auth()->user();

        abort_unless(in_array($user?->role, ['registrar', 'admin'], true), 403);

        $requestDoc = DB::transaction(function () use ($request_id, $user): RequestDocument {
            $requestDoc = RequestDocument::query()->lockForUpdate()->findOrFail($request_id);
            abort_if(in_array($requestDoc->status, ['completed', 'rejected'], true), 422);

            if (! $requestDoc->payment_confirmed) {
                $requestDoc->update([
                    'payment_confirmed' => true,
                    'payment_confirmed_at' => now(),
                    'payment_confirmed_by' => $user->id,
                ]);
            }

            return $requestDoc;
        });

        record_log('Payment Confirmed', 'Requests', "Confirmed payment for Request #{$request_id} (Ticket: {$requestDoc->ticket_number})");

        return redirect()->back()->with('success', 'Payment confirmed successfully.');
    }

    public function updateClearance(Request $request, $request_id)
    {
        $user = auth()->user();

        abort_unless(in_array($user?->role, ['registrar', 'admin'], true), 403);

        $request->validate([
            'clearance_status' => 'required|string|in:cleared,pending_clearance,has_balance',
            'financial_balance' => 'nullable|numeric|min:0',
        ]);

        $requestDoc = RequestDocument::findOrFail($request_id);

        $requestDoc->update([
            'clearance_status' => $request->clearance_status,
            'financial_balance' => $request->financial_balance ?? 0,
        ]);

        record_log('Clearance Updated', 'Requests', "Updated clearance for Request #{$request_id} to {$request->clearance_status}");

        return redirect()->back()->with('success', 'Clearance status updated successfully.');
    }

    public function updateStatus(Request $request, $request_id, RequestStatusTransitions $transitions, \App\Support\RequestNotificationService $notifications)
    {
        $user = auth()->user();
        abort_unless(in_array($user?->role, ['registrar', 'admin', 'records_officer'], true), 403);

        $request->validate([
            'status' => 'required|string|in:pending,processing,processed,ready_to_release,completed,rejected',
            'remarks' => 'nullable|string|max:1000',
        ]);

        $requestDoc = DB::transaction(function () use ($request, $request_id, $user, $transitions): RequestDocument {
            $lockedRequest = RequestDocument::query()->lockForUpdate()->findOrFail($request_id);

            $transitions->validate($lockedRequest, $user, $request->status, $request->remarks);

            $lockedRequest->update([
                'status' => $request->status,
                'remarks' => $request->remarks,
            ]);

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

        if ($requestDoc->wasChanged('status')) {
            $notifications->statusChanged($requestDoc);
        }

        record_log('Updated Request Status', 'Requests', "Updated Request #{$request_id} status to {$request->status}");

        if (
            $request->status === 'processing'
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

        return redirect()->back()->with('success', 'Request status updated to '.str_replace('_', ' ', $request->status));
    }

    public function clearHistory(Request $request)
    {
        abort_unless($request->user()?->role === 'admin', 403);

        $validated = $request->validate([
            'action' => 'required|string|in:completed,rejected,all',
        ]);

        $action = $request->input('action');

        if ($action === 'all') {
            $count = RequestDocument::whereIn('status', ['completed', 'rejected'])->delete();
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

    public function resetAll()
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $count = RequestDocument::count();
        RequestDocument::truncate();

        record_log('Full Request System Reset', 'Requests', "Permanently deleted all {$count} request records from the system.");

        return redirect()->back()->with('success', 'System Reset Successful: All requests and history have been cleared.');
    }
}
