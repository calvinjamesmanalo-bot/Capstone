<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentAccountActivation;
use App\Models\User;
use App\Notifications\VerifyStudentAccountCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;
use Throwable;

class StudentRegistrationController extends Controller
{
    private const CODE_LIFETIME_MINUTES = 10;

    private const MAX_CODE_ATTEMPTS = 5;

    public function create(): View
    {
        return view('auth.create-student-account');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'student_number' => Str::upper(trim((string) $request->input('student_number'))),
            'name' => trim((string) $request->input('name')),
            'email' => Str::lower(trim((string) $request->input('email'))),
        ]);

        $validated = $request->validate([
            'student_number' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', 'ends_with:@gmail.com'],
            'password' => ['required', 'confirmed', PasswordRule::min(12)->max(64)->mixedCase()->numbers(), 'regex:/[^\pL\pN\s]/u'],
        ], array_merge($this->passwordValidationMessages(), [
            'email.ends_with' => 'Use the official Gmail address registered in the student roster.',
        ]));

        $student = Student::find($validated['student_number']);
        $requestToken = Str::random(64);
        $requiresRoster = (bool) config('security.student_registration.require_roster', false);
        $rosterMatches = ! $requiresRoster || ($student !== null
            && $this->namesMatch($student->name, $validated['name'])
            && is_string($student->official_email)
            && hash_equals(Str::lower(trim($student->official_email)), $validated['email']));
        $accountCanBeCreated = $this->accountCanBeCreated($validated['student_number'], $validated['email']);
        $gmailCanBeAssigned = $this->gmailCanBeAssigned($validated['student_number'], $validated['email']);

        if ($rosterMatches
            && $accountCanBeCreated
            && $gmailCanBeAssigned) {
            $verificationCode = (string) random_int(100000, 999999);
            $activation = DB::transaction(function () use ($validated, $requestToken, $verificationCode): StudentAccountActivation {
                // A new request revokes every older code for this student.
                StudentAccountActivation::where('student_number', $validated['student_number'])->delete();

                return StudentAccountActivation::create([
                    'student_number' => $validated['student_number'],
                    'submitted_name' => $validated['name'],
                    'email' => $validated['email'],
                    'token_hash' => hash('sha256', $requestToken),
                    'verification_code_hash' => $this->verificationCodeHash($verificationCode),
                    'password_hash' => Hash::make($validated['password']),
                    'verification_attempts' => 0,
                    'expires_at' => now()->addMinutes(self::CODE_LIFETIME_MINUTES),
                ]);
            });

            try {
                Notification::route('mail', $validated['email'])->notify(
                    new VerifyStudentAccountCode($verificationCode, self::CODE_LIFETIME_MINUTES)
                );
                Log::channel('security')->notice('Student verification code accepted by the mail transport.', [
                    'registration_fingerprint' => $this->registrationFingerprint(
                        $validated['student_number'],
                        $validated['email']
                    ),
                ]);
            } catch (Throwable $exception) {
                // Do not log the Gmail address, password, verification code, or token.
                $activation->delete();
                Log::channel('security')->error('Student verification code email could not be sent.', [
                    'exception' => $exception::class,
                ]);
            }
        } else {
            // Keep the public response generic to prevent roster/account
            // enumeration, while retaining non-PII diagnostics for operators.
            Log::channel('security')->notice('Student verification code was not sent because the registration was ineligible.', [
                'registration_fingerprint' => $this->registrationFingerprint(
                    $validated['student_number'],
                    $validated['email']
                ),
                'roster_matched' => $rosterMatches,
                'account_available' => $accountCanBeCreated,
                'email_available' => $gmailCanBeAssigned,
            ]);
        }

        // Invalid roster details use an indistinguishable dummy token so this
        // endpoint cannot be used to discover official student records.
        return redirect()->route('student.registration.code', ['token' => $requestToken])->with(
            'status',
            'If the details match an eligible official student record, a verification code has been sent to the Gmail address.'
        );
    }

    public function codeForm(string $token): View
    {
        return view('auth.verify-student-code', ['token' => $token]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'regex:/^\d{6}$/'],
        ], [
            'code.regex' => 'Enter the six-digit verification code.',
        ]);

        $verified = DB::transaction(function () use ($validated): bool {
            $activation = StudentAccountActivation::query()
                ->where('token_hash', hash('sha256', $validated['token']))
                ->whereNull('used_at')
                ->lockForUpdate()
                ->first();

            if ($activation === null
                || $activation->expires_at->isPast()
                || $activation->verification_attempts >= self::MAX_CODE_ATTEMPTS
                || ! is_string($activation->verification_code_hash)
                || ! is_string($activation->password_hash)) {
                return false;
            }

            if (! hash_equals(
                $activation->verification_code_hash,
                $this->verificationCodeHash($validated['code'])
            )) {
                $activation->increment('verification_attempts');

                return false;
            }

            $student = Student::query()
                ->where('student_number', $activation->student_number)
                ->lockForUpdate()
                ->first();
            $requiresRoster = (bool) config('security.student_registration.require_roster', false);
            $rosterMatches = ! $requiresRoster || ($student !== null
                && $this->namesMatch($student->name, (string) $activation->submitted_name)
                && is_string($student->official_email)
                && hash_equals(Str::lower(trim($student->official_email)), $activation->email));

            if (! $rosterMatches
                || ! $this->accountCanBeCreated($activation->student_number, $activation->email)
                || ! $this->gmailCanBeAssigned($activation->student_number, $activation->email)) {
                return false;
            }

            if ($student === null) {
                $student = new Student;
                $student->student_number = $activation->student_number;
            }

            if (! $requiresRoster) {
                $student->name = (string) $activation->submitted_name;
                $student->official_email = $activation->email;
                $student->save();
            }

            $user = User::query()
                ->where('student_number', $student->student_number)
                ->orWhereRaw('LOWER(email) = ?', [$activation->email])
                ->lockForUpdate()
                ->first() ?? new User;

            $user->forceFill([
                'name' => $student->name,
                'email' => $activation->email,
                'email_verified_at' => now(),
                'pending_email' => null,
                'password' => $activation->password_hash,
                'role' => 'student',
                'student_number' => $student->student_number,
                'remember_token' => Str::random(60),
            ])->save();

            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->getKey())
                ->delete();
            DB::table(config('auth.passwords.users.table', 'password_reset_tokens'))
                ->where('email', $activation->email)
                ->delete();

            $activation->update(['used_at' => now()]);

            return true;
        });

        if (! $verified) {
            return back()->withErrors([
                'code' => 'The verification code is invalid or expired. Request a new code and try again.',
            ]);
        }

        return redirect()->route('login')->with(
            'status',
            'Your student account is verified and active. You can now sign in.'
        );
    }

    private function accountCanBeCreated(string $studentNumber, string $email): bool
    {
        $byStudentNumber = User::where('student_number', $studentNumber)->first();
        $byEmail = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if ($byStudentNumber === null && $byEmail === null) {
            return true;
        }

        return $byStudentNumber !== null
            && $byEmail !== null
            && $byStudentNumber->is($byEmail)
            && $byStudentNumber->role === 'student'
            && ! $byStudentNumber->hasVerifiedEmail()
            && hash_equals(Str::lower(trim($byStudentNumber->email)), $email);
    }

    private function gmailCanBeAssigned(string $studentNumber, string $email): bool
    {
        $studentUsingEmail = Student::whereRaw('LOWER(official_email) = ?', [$email])->first();

        return $studentUsingEmail === null
            || hash_equals((string) $studentUsingEmail->student_number, $studentNumber);
    }

    private function namesMatch(string $officialName, string $submittedName): bool
    {
        return hash_equals(
            $this->nameFingerprint($officialName),
            $this->nameFingerprint($submittedName)
        );
    }

    private function nameFingerprint(string $name): string
    {
        $tokens = preg_split('/[^a-z0-9]+/', Str::lower(Str::ascii($name)), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        sort($tokens, SORT_STRING);

        return implode('|', $tokens);
    }

    private function verificationCodeHash(string $code): string
    {
        return hash_hmac('sha256', $code, (string) config('app.key'));
    }

    private function registrationFingerprint(string $studentNumber, string $email): string
    {
        return hash_hmac(
            'sha256',
            Str::upper(trim($studentNumber)).'|'.Str::lower(trim($email)),
            (string) config('app.key')
        );
    }

    private function passwordValidationMessages(): array
    {
        return [
            'password.min' => 'The password is too short. Use at least 12 characters.',
            'password.max' => 'The password is too long. Use no more than 64 characters.',
            'password.mixed' => 'The password is too weak. Include uppercase and lowercase letters.',
            'password.numbers' => 'The password is too weak. Include at least one number.',
            'password.regex' => 'The password is too weak. Include at least one symbol.',
            'password.confirmed' => 'The password confirmation does not match.',
        ];
    }
}
