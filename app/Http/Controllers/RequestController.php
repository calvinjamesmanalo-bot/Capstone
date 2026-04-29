<?php

namespace App\Http\Controllers;

use App\Models\RequestDocument;
use App\Models\Student;
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
        $requests = RequestDocument::with('student')
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

        return view('requests.student', compact('activeRequests', 'requestHistory', 'studentNumber'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        
        // Validation changes based on user role
        $rules = [
            'document_type' => 'required|string',
        ];

        if (!$user || $user->role !== 'student') {
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

        RequestDocument::create([
            'student_number' => $studentNumber,
            'document_type' => $request->document_type,
            'status' => 'pending',
        ]);

        record_log('Submitted Request', 'Requests', "Student #{$studentNumber} requested {$request->document_type}");

        return redirect()->back()->with('success', 'Your request has been submitted!');
    }

    public function updateStatus(Request $request, $request_id)
    {
        $user = auth()->user();
        $request->validate([
            'status' => 'required|string|in:pending,processing,processed,ready_to_release,completed,rejected',
            'remarks' => 'nullable|string'
        ]);

        $requestDoc = RequestDocument::findOrFail($request_id);

        // Security check for Registrar approval
        if ($request->status === 'ready_to_release' && $user->role !== 'registrar' && $user->role !== 'admin') {
            return redirect()->back()->with('error', 'Only the Registrar can approve requests for release.');
        }

        $requestDoc->update([
            'status' => $request->status,
            'remarks' => $request->remarks
        ]);

        record_log('Updated Request Status', 'Requests', "Updated Request #{$request_id} status to {$request->status}");

        return redirect()->back()->with('success', 'Request status updated to ' . str_replace('_', ' ', $request->status));
    }

    public function clearHistory(Request $request)
    {
        $validated = $request->validate([
            'action' => 'required|string|in:completed,rejected,all'
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
}
