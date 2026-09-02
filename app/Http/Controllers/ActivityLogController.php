<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ActivityLog;
use App\Models\DocumentVerificationAudit;

class ActivityLogController extends Controller
{
    public function index()
    {
        $logs = ActivityLog::with('user')->orderBy('created_at', 'desc')->paginate(10);
        $verificationAudits = DocumentVerificationAudit::with(['user', 'documentAuthenticity'])
            ->latest('verified_at')
            ->paginate(10, ['*'], 'verification_page');

        return view('logs.index', compact('logs', 'verificationAudits'));
    }

    public function clear()
    {
        ActivityLog::truncate();
        DocumentVerificationAudit::truncate();
        
        record_log('Cleared System Logs', 'System', 'All activity logs have been permanently deleted');
        
        return redirect()->route('logs.index')->with('success', 'System logs cleared successfully.');
    }
}
