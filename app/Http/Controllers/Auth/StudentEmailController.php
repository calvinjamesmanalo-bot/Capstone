<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\StudentEmailChanged;
use App\Notifications\VerifyStudentEmailChange;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StudentEmailController extends Controller
{
    public function edit(Request $request): View
    {
        return view('auth.change-email', ['user' => $request->user()]);
    }

    public function requestChange(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->role === 'student' && $user->student, 403);

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
                Rule::unique('users', 'pending_email')->ignore($user->id),
                Rule::unique('students', 'official_email')->ignore($user->student_number, 'student_number'),
            ],
        ]);

        $newEmail = Str::lower(trim($validated['email']));
        if (hash_equals(Str::lower($user->email), $newEmail)) {
            throw ValidationException::withMessages([
                'email' => 'The new email must be different from your current email.',
            ]);
        }

        $user->forceFill(['pending_email' => $newEmail])->save();

        $url = URL::temporarySignedRoute(
            'student.email.verify',
            now()->addMinutes(60),
            ['user' => $user->id, 'hash' => hash('sha256', $newEmail)]
        );

        Notification::route('mail', $newEmail)
            ->notify(new VerifyStudentEmailChange($url));

        record_log('Email Change Requested', 'Account Security', 'Requested verification of a new account email address');

        return back()->with('status', 'A verification link has been sent to your new email address.');
    }

    public function verify(Request $request, User $user, string $hash): RedirectResponse
    {
        abort_unless($request->user()->is($user) && $user->role === 'student', 403);
        abort_unless($user->pending_email !== null, 403);
        abort_unless(hash_equals(hash('sha256', $user->pending_email), $hash), 403);

        $oldEmail = $user->email;
        $newEmail = $user->pending_email;

        DB::transaction(function () use ($user, $newEmail): void {
            $user->student()->update(['official_email' => $newEmail]);
            $user->forceFill([
                'email' => $newEmail,
                'pending_email' => null,
                'email_verified_at' => now(),
            ])->save();
        });

        DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->id)
            ->where('id', '!=', $request->session()->getId())
            ->delete();

        $request->session()->regenerate(true);

        Notification::route('mail', $oldEmail)
            ->notify(new StudentEmailChanged($newEmail));

        record_log('Email Changed', 'Account Security', 'Verified and changed the official account email address');

        return redirect()->route('student.email.edit')->with('status', 'Your email address has been changed and verified.');
    }
}
