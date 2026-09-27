<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Grade;
use App\Models\RequestDocument;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $role = $user->role ?? 'admin';

        $data = [];

        if (in_array($role, ['admin', 'registrar', 'records_officer'])) {
            $data['total_users'] = User::count();
            $data['total_requests'] = RequestDocument::count();
            $data['pending_requests'] = RequestDocument::where('status', 'pending')->count();
            $data['completed_requests'] = RequestDocument::where('status', 'completed')->count();
            $data['recent_logs'] = ActivityLog::with('user')->latest()->limit(5)->get();
            $data['recent_requests'] = RequestDocument::with('student')->latest()->limit(5)->get();
            $data['total_grades'] = Grade::count();
            if ($role === 'admin') {
                $data['request_statuses'] = RequestDocument::query()
                    ->selectRaw('status, COUNT(*) as total')
                    ->groupBy('status')
                    ->pluck('total', 'status');
                $data['user_roles'] = User::query()
                    ->selectRaw('role, COUNT(*) as total')
                    ->groupBy('role')
                    ->pluck('total', 'role');
                $data['unverified_users'] = User::whereNull('email_verified_at')->count();
                $data['completed_today'] = RequestDocument::where('status', 'completed')
                    ->whereDate('updated_at', today())
                    ->count();
                $data['recent_logs'] = ActivityLog::with('user')->latest()->limit(8)->get();
                $data['recent_requests'] = RequestDocument::with('student')->latest()->limit(8)->get();
            }
        } elseif ($role === 'student') {
            $data['announcements'] = \App\Models\Announcement::latest('id')->get();
            $student_number = $user->student_number ?? session('student_number');

            if ($student_number) {
                // Active Requests: Pending, Processing, Processed (Registrar Approval), Ready to Release
                $data['active_requests'] = RequestDocument::where('student_number', $student_number)
                    ->whereIn('status', ['pending', 'processing', 'processed', 'ready_to_release'])
                    ->latest()
                    ->get();

                // History: Completed or Rejected
                $data['request_history'] = RequestDocument::where('student_number', $student_number)
                    ->whereIn('status', ['completed', 'rejected'])
                    ->latest()
                    ->limit(10)
                    ->get();

                $data['pending_my_requests'] = $data['active_requests']->where('status', 'pending')->count();
                $data['total_requests'] = RequestDocument::where('student_number', $student_number)->count();
            } else {
                $data['active_requests'] = collect();
                $data['request_history'] = collect();
                $data['pending_my_requests'] = 0;
                $data['total_requests'] = 0;
            }
        }

        return view($role === 'admin' ? 'dashboard-admin' : 'dashboard', compact('data', 'role'));
    }
}
