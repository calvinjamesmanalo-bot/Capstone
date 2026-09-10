<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use App\Support\AuthenticationSecurity;
use App\Support\TurnstileVerifier;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return $this->renderLoginForm('student');
    }

    public function showStaffLoginForm()
    {
        return $this->renderLoginForm('staff');
    }

    public function showAdminLoginForm()
    {
        return $this->renderLoginForm('admin');
    }

    private function renderLoginForm(string $accountType)
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $portal = match ($accountType) {
            'staff' => [
                'title' => 'Staff Sign In',
                'description' => 'Access registrar and records management tools.',
                'identifier_label' => 'Email address',
                'identifier_placeholder' => 'staff@fiat.edu.ph',
                'identifier_type' => 'email',
                'form_action' => route('login.staff.submit'),
            ],
            'admin' => [
                'title' => 'Admin Sign In',
                'description' => 'Access protected system administration tools.',
                'identifier_label' => 'Email address',
                'identifier_placeholder' => 'admin@fiat.edu.ph',
                'identifier_type' => 'email',
                'form_action' => route('login.admin.submit'),
            ],
            default => [
                'title' => 'Student Sign In',
                'description' => 'Access your document requests and academic records.',
                'identifier_label' => 'Student Number',
                'identifier_placeholder' => 'e.g. 2023-1234',
                'identifier_type' => 'text',
                'form_action' => route('login'),
            ],
        };

        return view('auth.login', [
            'formStartedAt' => Crypt::encryptString((string) now()->timestamp),
            'accountType' => $accountType,
            'portal' => $portal,
        ]);
    }

    public function login(
        Request $request,
        AuthenticationSecurity $security,
        TurnstileVerifier $turnstile
    ) {
        return $this->authenticate($request, $security, $turnstile, 'student');
    }

    public function loginStaff(
        Request $request,
        AuthenticationSecurity $security,
        TurnstileVerifier $turnstile
    ) {
        return $this->authenticate($request, $security, $turnstile, 'staff');
    }

    public function loginAdmin(
        Request $request,
        AuthenticationSecurity $security,
        TurnstileVerifier $turnstile
    ) {
        return $this->authenticate($request, $security, $turnstile, 'admin');
    }

    private function authenticate(
        Request $request,
        AuthenticationSecurity $security,
        TurnstileVerifier $turnstile,
        string $accountType
    ) {
        // The route determines the role. Any forged frontend value is overwritten.
        $request->merge(['account_type' => $accountType]);

        $credentials = $request->validate([
            'identifier' => [
                'required',
                'string',
                'max:255',
                Rule::when(
                    $request->input('account_type') === 'student',
                    ['max:50', 'regex:/^[A-Za-z0-9][A-Za-z0-9._-]*$/'],
                    ['email:rfc']
                ),
            ],
            'password' => ['required', 'string', 'max:1024'],
            'account_type' => ['required', Rule::in(['student', 'staff', 'admin'])],
            'cf-turnstile-response' => ['nullable', 'string', 'max:2048'],
            'website' => ['nullable', 'string', 'max:255'],
            'form_started_at' => ['required', 'string', 'max:1024'],
        ], [
            'identifier.regex' => 'Enter a valid student number.',
            'identifier.email' => 'Enter a valid email address.',
        ]);

        $identifier = trim($credentials['identifier']);
        if ($credentials['account_type'] === 'student') {
            $identifier = Str::upper($identifier);
        } else {
            $identifier = Str::lower($identifier);
        }

        $ipAddress = $request->ip();
        $userAgent = $request->userAgent();

        if ($this->looksAutomated($credentials)) {
            $security->recordFailure(
                $identifier,
                $ipAddress,
                $userAgent,
                $credentials['account_type'],
                'automation_check_failed'
            );

            return $this->failedLoginResponse();
        }

        $seconds = $security->secondsUntilAvailable($identifier, $ipAddress);

        if ($seconds > 0) {
            $security->recordBlockedAttempt(
                $identifier,
                $ipAddress,
                $userAgent,
                $credentials['account_type']
            );

            return $this->lockoutResponse($seconds);
        }

        if (! $turnstile->verify($credentials['cf-turnstile-response'] ?? null, $ipAddress)) {
            $security->recordFailure(
                $identifier,
                $ipAddress,
                $userAgent,
                $credentials['account_type'],
                'captcha_failed'
            );

            return $this->failedLoginResponse();
        }

        $loginCredentials = [
            $credentials['account_type'] === 'student' ? 'student_number' : 'email' => $identifier,
            'password' => $credentials['password'],
        ];
        $remember = $credentials['account_type'] !== 'admin' && $request->boolean('remember');

        try {
            $authenticated = Auth::attempt($loginCredentials, $remember);
        } catch (\RuntimeException) {
            // Strict Argon2id verification rejects legacy hashes. The same public
            // response is used; affected users can securely reset their password.
            $authenticated = false;
        }

        if ($authenticated) {
            $user = $request->user();

            if (! $this->roleMatchesAccountType($user, $credentials['account_type'])
                || ! $user->hasMatchingOfficialStudentRecord()) {
                Auth::logout();
                $security->recordFailure(
                    $identifier,
                    $ipAddress,
                    $userAgent,
                    $credentials['account_type'],
                    'account_policy_mismatch'
                );

                return $this->failedLoginResponse();
            }

            $security->clearAccount($identifier);
            $request->session()->regenerate();

            if ($user->role === 'student' && ! $user->hasVerifiedEmail()) {
                return redirect()->route('verification.notice');
            }

            return redirect()->intended(route('dashboard'));
        }

        $security->recordFailure(
            $identifier,
            $ipAddress,
            $userAgent,
            $credentials['account_type'],
            'invalid_credentials'
        );

        return $this->failedLoginResponse();
    }

    private function roleMatchesAccountType(User $user, string $accountType): bool
    {
        return match ($accountType) {
            'student' => $user->role === 'student',
            'staff' => in_array($user->role, ['registrar', 'records_officer'], true),
            'admin' => $user->role === 'admin',
            default => false,
        };
    }

    private function looksAutomated(array $credentials): bool
    {
        if (trim((string) ($credentials['website'] ?? '')) !== '') {
            return true;
        }

        try {
            $startedAt = (int) Crypt::decryptString($credentials['form_started_at']);
        } catch (DecryptException) {
            return true;
        }

        $age = now()->timestamp - $startedAt;
        $minimumAge = max(0, (int) config('security.authentication.minimum_form_fill_seconds', 2));
        $maximumAge = max($minimumAge + 1, (int) config('security.authentication.maximum_form_age_seconds', 3600));

        return $age < $minimumAge || $age > $maximumAge;
    }

    private function failedLoginResponse()
    {
        return back()->withErrors([
            'identifier' => 'Unable to sign in with the provided credentials. Please try again later.',
        ])->onlyInput('identifier', 'account_type');
    }

    private function lockoutResponse(int $seconds)
    {
        $minutes = max(1, (int) ceil($seconds / 60));
        $unit = $minutes === 1 ? 'minute' : 'minutes';

        return back()->withErrors([
            'identifier' => "Too many login attempts. Please try again after {$minutes} {$unit}.",
        ])->onlyInput('identifier', 'account_type');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function loginAsRole(string $role)
    {
        abort_unless(app()->environment(['local', 'testing']), 404);

        $accounts = [
            'student' => ['name' => 'Chua,,Louisse,', 'email' => 'student@example.com', 'student_number' => '2023-0001'],
            'registrar' => ['name' => 'Registrar,,Test,', 'email' => 'registrar@example.com'],
            'records_officer' => ['name' => 'Records Officer,,Test,', 'email' => 'records_officer@example.com'],
            'admin' => ['name' => 'Administrator,,Test,', 'email' => 'admin@example.com'],
        ];

        abort_unless(array_key_exists($role, $accounts), 404);

        $account = $accounts[$role];
        if ($role === 'student') {
            Student::updateOrCreate(
                ['student_number' => $account['student_number']],
                ['name' => 'Louisse Chua', 'official_email' => $account['email']]
            );
        }

        $user = User::firstOrCreate(
            ['email' => $account['email']],
            [
                'name' => $account['name'],
                'password' => Hash::make(Str::random(40)),
                'role' => $role,
                'student_number' => $account['student_number'] ?? null,
            ]
        );

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        Auth::login($user);
        session()->regenerate();

        return redirect()->route('dashboard')->with('success', "Logged in as {$role}");
    }
}
