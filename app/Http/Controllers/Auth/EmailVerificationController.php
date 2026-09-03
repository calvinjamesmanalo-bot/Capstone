<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    public function notice(Request $request): View|RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        abort_unless($request->user()->hasMatchingOfficialStudentRecord(), 403);

        return view('auth.verify-email');
    }

    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        abort_unless($request->user()->hasMatchingOfficialStudentRecord(), 403);

        if (! $request->user()->hasVerifiedEmail()) {
            $request->fulfill();
            record_log('Email Verified', 'Account Security', 'Verified the official account email address');
        }

        return redirect()->route('dashboard')->with('success', 'Your email address has been verified.');
    }

    public function send(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        abort_unless($request->user()->hasMatchingOfficialStudentRecord(), 403);
        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'A new verification link has been sent to your official email address.');
    }
}
