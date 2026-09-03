<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function requestForm(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = Str::lower(trim((string) $request->input('email')));
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if ($this->canResetPassword($user)) {
            $user->sendPasswordResetNotification(
                Password::broker()->createToken($user)
            );
        }

        // Always return the same response so this form cannot reveal accounts.
        return back()->with(
            'status',
            'If an account exists for that email address, a password reset link has been sent.'
        );
    }

    public function resetForm(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'email' => $request->query('email'),
            'token' => $token,
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(12)->max(64)->mixedCase()->numbers(), 'regex:/[^\pL\pN\s]/u'],
        ], [
            'password.min' => 'The password is too short. Use at least 12 characters.',
            'password.max' => 'The password is too long. Use no more than 64 characters.',
            'password.mixed' => 'The password is too weak. Include uppercase and lowercase letters.',
            'password.numbers' => 'The password is too weak. Include at least one number.',
            'password.regex' => 'The password is too weak. Include at least one symbol.',
            'password.confirmed' => 'The password confirmation does not match.',
        ]);

        $credentials['email'] = Str::lower(trim($credentials['email']));

        $user = User::whereRaw('LOWER(email) = ?', [$credentials['email']])->first();
        if (! $this->canResetPassword($user)) {
            return $this->invalidResetResponse($request);
        }

        $status = Password::reset(
            $credentials,
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                // A recovered account should not retain any existing sessions.
                DB::table(config('session.table', 'sessions'))
                    ->where('user_id', $user->getKey())
                    ->delete();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with(
                'status',
                'Your password has been reset. You can now sign in.'
            );
        }

        return $this->invalidResetResponse($request);
    }

    private function canResetPassword(?User $user): bool
    {
        return $user !== null
            && $user->hasVerifiedEmail()
            && $user->hasMatchingOfficialStudentRecord();
    }

    private function invalidResetResponse(Request $request): RedirectResponse
    {
        return back()
            ->withErrors(['email' => 'This password reset link is invalid or has expired.'])
            ->withInput($request->only('email'));
    }
}
