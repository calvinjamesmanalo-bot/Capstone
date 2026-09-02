<?php

namespace App\Http\Controllers;

use App\Models\RequestDocument;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;

class RequestController extends Controller
{
    // Records Officer / Registrar View
    public function index()
    {
        $user = auth()->user();
        $query = RequestDocument::with('student');

        if ($user->role === 'registrar') {
            // Registrar only sees processed requests that need approval
            $query->where('status', 'processed');
        } elseif ($user->role === 'admin') {
            // Admin sees all active requests
            $query->whereNotIn('status', ['completed', 'rejected']);
        } else {
            // Records officer sees everything for management
            $query->whereNotIn('status', ['completed', 'rejected']);
        }

        $requests = $query->latest()->get();

        return view('requests.index', compact('requests'));
    }

    public function history()
    {
        $requests = RequestDocument::with([
            'student',
            'authenticities' => fn ($query) => $query->latest('id'),
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
            $activeRequests = RequestDocument::where('student_number', $studentNumber)
                ->whereIn('status', ['pending', 'processing', 'processed', 'ready_to_release'])
                ->latest()
                ->get();

            $requestHistory = RequestDocument::where('student_number', $studentNumber)
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
            $activeRequests = RequestDocument::where('student_number', $studentNumber)
                ->whereIn('status', ['pending', 'processing', 'processed', 'ready_to_release'])
                ->latest()
                ->get();

            $requestHistory = RequestDocument::where('student_number', $studentNumber)
                ->whereIn('status', ['completed', 'rejected'])
                ->latest()
                ->get();
        }

        return view('requests.my-requests', compact('activeRequests', 'requestHistory', 'studentNumber'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        // Validation changes based on user role and payment method
        $rules = [
            'document_type' => 'required|string|in:Form 137,Form 138,Certificate of Enrollment,Certificate of Completion,Certificate of Good Moral Character,Certificate of Recognition,Diploma',
            'school_year' => 'required_if:document_type,Form 138|nullable|regex:/^\d{4}-\d{4}$/',
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

        // Simulate clearance check - in real system, this would query a finance database
        $clearanceStatus = 'cleared';
        $financialBalance = 0.00;

        // For demonstration: randomly assign balance to some requests
        if (rand(1, 10) <= 2) {
            $clearanceStatus = 'has_balance';
            $financialBalance = rand(500, 5000);
        }

        // Generate Ticket Number: REQ-YYYY-XXXX (where XXXX is a unique random string or increment)
        $ticketNumber = 'REQ-'.date('Y').'-'.strtoupper(bin2hex(random_bytes(3)));

        // Ensure uniqueness
        while (RequestDocument::where('ticket_number', $ticketNumber)->exists()) {
            $ticketNumber = 'REQ-'.date('Y').'-'.strtoupper(bin2hex(random_bytes(3)));
        }

        // Keep using the existing database column for compatibility with prior requests.
        $transcriptReceiptPath = $request->file('transcript_receipt')
            ->store('transcript_receipts', 'public');

        $documentPrice = $this->documentPrices()[$request->document_type];

        RequestDocument::create([
            'ticket_number' => $ticketNumber,
            'student_number' => $studentNumber,
            'document_type' => $request->document_type,
            'school_year' => $request->document_type === 'Form 138' ? $request->school_year : null,
            'document_price' => $documentPrice,
            'delivery_method' => $request->delivery_method,
            'payment_method' => $request->payment_method,
            'release_location' => $request->release_location,
            'payment_proof_path' => $transcriptReceiptPath,
            'clearance_status' => $clearanceStatus,
            'financial_balance' => $financialBalance,
            'payment_confirmed' => false,
            'status' => 'pending',
        ]);

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
        if ($clearanceStatus === 'has_balance') {
            $message .= ' Note: You have an outstanding balance of ₱'.number_format($financialBalance, 2).'. Please settle this before your document can be released.';
        }

        return redirect()->back()->with('success', $message);
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

        if (! in_array($user->role, ['registrar', 'admin', 'records_officer'])) {
            return redirect()->back()->with('error', 'Unauthorized access.');
        }

        $requestDoc = RequestDocument::findOrFail($request_id);

        $requestDoc->update([
            'payment_confirmed' => true,
            'clearance_status' => 'cleared',
        ]);

        record_log('Payment Confirmed', 'Requests', "Confirmed payment for Request #{$request_id} (Ticket: {$requestDoc->ticket_number})");

        return redirect()->back()->with('success', 'Payment confirmed successfully.');
    }

    public function updateClearance(Request $request, $request_id)
    {
        $user = auth()->user();

        if (! in_array($user->role, ['registrar', 'admin'])) {
            return redirect()->back()->with('error', 'Only Registrar or Admin can update clearance status.');
        }

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

    public function updateStatus(Request $request, $request_id)
    {
        $user = auth()->user();
        $request->validate([
            'status' => 'required|string|in:pending,processing,processed,ready_to_release,completed,rejected',
            'remarks' => 'nullable|string',
        ]);

        $requestDoc = RequestDocument::findOrFail($request_id);

        // Security check for Registrar approval
        if ($request->status === 'ready_to_release' && $user->role !== 'registrar' && $user->role !== 'admin') {
            return redirect()->back()->with('error', 'Only the Registrar can approve requests for release.');
        }

        $requestDoc->update([
            'status' => $request->status,
            'remarks' => $request->remarks,
        ]);

        if ($request->status === 'rejected') {
            $requestDoc->authenticities()
                ->whereIn('status', ['valid', 'superseded'])
                ->update([
                    'status' => 'revoked',
                    'revoked_at' => now(),
                    'revoked_by' => $user->id,
                    'revocation_reason' => $request->remarks ?: 'The related document request was rejected.',
                ]);
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
        if (auth()->user()->role !== 'admin') {
            return redirect()->back()->with('error', 'Only Admins can perform a full system reset of requests.');
        }

        $count = RequestDocument::count();
        RequestDocument::truncate();

        record_log('Full Request System Reset', 'Requests', "Permanently deleted all {$count} request records from the system.");

        return redirect()->back()->with('success', 'System Reset Successful: All requests and history have been cleared.');
    }
}
