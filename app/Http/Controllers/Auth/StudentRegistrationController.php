<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentAccountActivation;
use App\Models\User;
use App\Support\TurnstileVerifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;
use Throwable;

class StudentRegistrationController extends Controller
{
    private const IDENTITY_LIFETIME_MINUTES = 15;

    public function create(Request $request): View
    {
        if ($request->boolean('restart')) {
            $request->session()->forget('student_registration_identity');
        }

        $identity = $request->session()->get('student_registration_identity');
        if (! is_array($identity)
            || now()->timestamp - ($identity['verified_at'] ?? 0) > self::IDENTITY_LIFETIME_MINUTES * 60
            || ! isset($identity['verification_student_number'])) {
            $request->session()->forget('student_registration_identity');
            $identity = null;
        }

        return view('auth.create-student-account', array_merge(
            ['identity' => $identity],
            $this->dailyCapacity()
        ));
    }

    public function lookup(Request $request, TurnstileVerifier $turnstile): RedirectResponse
    {
        if ($this->dailyCapacity()['remainingAccounts'] < 1) {
            return redirect()->route('student.registration.request')->withErrors([
                'student_number' => 'The daily limit of 200 new student accounts has been reached. Please return tomorrow after 12:00 AM (Philippine time).',
            ]);
        }

        $request->merge([
            'student_number' => Str::upper(trim((string) $request->input('student_number'))),
            'first_name' => trim((string) $request->input('first_name')),
            'middle_name' => trim((string) $request->input('middle_name')),
            'last_name' => trim((string) $request->input('last_name')),
            'email' => Str::lower(trim((string) $request->input('email'))),
        ]);

        $validated = $request->validate([
            'student_number' => ['required', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', 'ends_with:@gmail.com'],
            'school_id' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'school_id_selfie' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'cf-turnstile-response' => ['nullable', 'string', 'max:2048'],
        ]);

        if (! $turnstile->verify($validated['cf-turnstile-response'] ?? null, $request->ip(), 'student_registration')) {
            return back()->withInput($request->except(['activation_code', 'cf-turnstile-response']))->withErrors([
                'student_number' => 'The security check could not be verified. Please try again.',
            ]);
        }

        $student = Student::find($validated['student_number']);
        if ($student === null
            || ! $student->masterlist_active
            || ! $this->partialNameMatches($student->name, $validated['first_name'], $validated['last_name'])) {
            $request->session()->forget('student_registration_identity');

            return back()->withInput()->withErrors([
                'student_number' => 'No matching active student record was found. Check the Student Number, First Name, and Last Name.',
            ]);
        }

        if (! $this->accountCanBeCreated($student->student_number, $validated['email'])
            || ! $this->gmailCanBeAssigned($student->student_number, $validated['email'])) {
            return back()->withInput($request->except(['school_id', 'school_id_selfie', 'cf-turnstile-response']))
                ->withErrors(['email' => 'An account or verification request already uses this Student Number or Gmail address.']);
        }

        Storage::disk('local')->delete(array_filter([$student->school_id_path, $student->school_id_selfie_path]));
        $schoolIdPath = $request->file('school_id')->store('student-identity-proofs', 'local');
        try {
            $selfiePath = $request->file('school_id_selfie')->store('student-identity-proofs', 'local');
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($schoolIdPath);
            throw $exception;
        }

        $statusToken = Str::random(64);
        $student->forceFill([
            'account_verification_status' => 'pending',
            'account_verification_email' => $validated['email'],
            'school_id_path' => $schoolIdPath,
            'school_id_selfie_path' => $selfiePath,
            'account_verification_token_hash' => hash('sha256', $statusToken),
            'account_verification_expires_at' => now()->addHours(max(1, (int) config('security.student_registration.identity_approval_hours', 72))),
            'account_verification_reviewed_by' => null,
            'account_verification_reviewed_at' => null,
            'account_verification_notes' => null,
        ])->save();
        $request->session()->forget('student_registration_identity');

        StudentAccountActivation::where('student_number', $student->student_number)->delete();

        return redirect()->route('student.registration.status', ['token' => $statusToken]);
    }

    public function status(string $token): View
    {
        $student = Student::query()
            ->where('account_verification_token_hash', hash('sha256', $token))
            ->first();

        return view('auth.student-registration-status', [
            'student' => $student,
            'token' => $token,
            'expired' => $student?->account_verification_expires_at?->isPast() ?? true,
        ]);
    }

    public function continueAfterApproval(Request $request, string $token): RedirectResponse
    {
        $student = Student::query()
            ->where('account_verification_token_hash', hash('sha256', $token))
            ->where('account_verification_status', 'approved')
            ->first();

        if ($student === null || $student->account_verification_expires_at?->isPast()) {
            return redirect()->route('student.registration.request')->withErrors([
                'student_number' => 'This approval link is invalid or expired. Submit new School ID photos or contact the Admin.',
            ]);
        }

        $request->session()->put('student_registration_identity', [
            'student_number' => $student->student_number,
            'name' => $student->name,
            'email' => $student->account_verification_email,
            'verification_student_number' => $student->student_number,
            'verified_at' => now()->timestamp,
        ]);

        return redirect()->route('student.registration.request');
    }

    public function store(Request $request): RedirectResponse
    {
        $identity = $request->session()->get('student_registration_identity');
        if (! is_array($identity)
            || now()->timestamp - ($identity['verified_at'] ?? 0) > self::IDENTITY_LIFETIME_MINUTES * 60
            || ! isset($identity['verification_student_number'])) {
            $request->session()->forget('student_registration_identity');

            return redirect()->route('student.registration.request')->withErrors([
                'student_number' => 'Please verify your student details again before setting your account password.',
            ]);
        }

        $validated = $request->validate([
            'password' => ['required', 'confirmed', PasswordRule::min(12)->max(64)->mixedCase()->numbers(), 'regex:/[^\pL\pN\s]/u'],
        ], $this->passwordValidationMessages());

        $validated['student_number'] = $identity['student_number'];
        $validated['name'] = $identity['name'];
        $validated['email'] = $identity['email'];

        $student = Student::find($validated['student_number']);
        $requiresRoster = (bool) config('security.student_registration.require_roster', false);
        $rosterMatches = ! $requiresRoster || ($student !== null
            && $student->masterlist_active
            && $this->namesMatch($student->name, $validated['name']));
        $accountCanBeCreated = $this->accountCanBeCreated($validated['student_number'], $validated['email']);
        $gmailCanBeAssigned = $this->gmailCanBeAssigned($validated['student_number'], $validated['email']);

        if (! $rosterMatches) {
            $request->session()->forget('student_registration_identity');

            return redirect()->route('student.registration.request')->withErrors([
                'student_number' => 'Your masterlist record changed. Please verify your student details again.',
            ]);
        }
        if (! $accountCanBeCreated) {
            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'An active account already exists for this Student Number or Gmail address. Use Sign In or Forgot Password instead.',
            ]);
        }
        if (! $gmailCanBeAssigned) {
            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'This Gmail address is already linked to another student record. Use a different Gmail address or contact the Registrar.',
            ]);
        }

        if ($this->dailyCapacity()['remainingAccounts'] < 1) {
            return back()->withErrors([
                'password' => 'The daily account creation limit has been reached. Please try again tomorrow.',
            ]);
        }

        DB::transaction(function () use ($validated): void {
            $student = Student::query()->lockForUpdate()->findOrFail($validated['student_number']);
            abort_unless($student->account_verification_status === 'approved'
                && hash_equals((string) $student->account_verification_email, $validated['email'])
                && $student->account_verification_expires_at?->isFuture(), 422, 'The identity approval is no longer valid.');

            $this->releaseStaleGmailReservation($student->student_number, $validated['email']);
            $student->official_email = $validated['email'];
            Storage::disk('local')->delete(array_filter([$student->school_id_path, $student->school_id_selfie_path]));
            $student->school_id_path = null;
            $student->school_id_selfie_path = null;
            $student->account_verification_status = 'completed';
            $student->account_verification_token_hash = null;
            $student->account_verification_expires_at = null;
            $student->save();

            User::create([
                'name' => $student->name,
                'email' => $validated['email'],
                'email_verified_at' => now(),
                'password' => Hash::make($validated['password']),
                'role' => 'student',
                'student_number' => $student->student_number,
                'remember_token' => Str::random(60),
            ]);
            StudentAccountActivation::where('student_number', $student->student_number)->delete();
        });

        $request->session()->forget('student_registration_identity');

        return redirect()->route('login')->with('status', 'Your student account is active. You can now sign in.');
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

        if ($studentUsingEmail === null
            || hash_equals((string) $studentUsingEmail->student_number, $studentNumber)) {
            return true;
        }

        // Old/inactive roster rows may contain test or outdated email values.
        // They must not reserve an address forever when no login account exists.
        return ! in_array($studentUsingEmail->account_verification_status, ['pending', 'approved'], true)
            && ! User::where('student_number', $studentUsingEmail->student_number)
                ->orWhereRaw('LOWER(email) = ?', [$email])
                ->exists();
    }

    private function releaseStaleGmailReservation(string $studentNumber, string $email): void
    {
        $staleStudent = Student::query()
            ->whereRaw('LOWER(official_email) = ?', [$email])
            ->where('student_number', '!=', $studentNumber)
            ->lockForUpdate()
            ->first();

        if ($staleStudent !== null
            && ! in_array($staleStudent->account_verification_status, ['pending', 'approved'], true)
            && ! User::where('student_number', $staleStudent->student_number)
                ->orWhereRaw('LOWER(email) = ?', [$email])
                ->exists()) {
            $staleStudent->update(['official_email' => null]);
        }
    }

    private function namesMatch(string $officialName, string $submittedName): bool
    {
        return hash_equals(
            $this->nameFingerprint($officialName),
            $this->nameFingerprint($submittedName)
        );
    }

    private function partialNameMatches(string $officialName, string $firstName, string $lastName): bool
    {
        $officialTokens = explode('|', $this->nameFingerprint($officialName));
        $submittedTokens = array_merge(
            explode('|', $this->nameFingerprint($firstName)),
            explode('|', $this->nameFingerprint($lastName))
        );

        return $submittedTokens !== []
            && count(array_diff($submittedTokens, $officialTokens)) === 0;
    }

    private function nameFingerprint(string $name): string
    {
        $tokens = preg_split('/[^a-z0-9]+/', Str::lower(Str::ascii($name)), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        sort($tokens, SORT_STRING);

        return implode('|', $tokens);
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

    private function dailyCapacity(): array
    {
        $limit = max(1, (int) config('security.student_registration.daily_account_limit', 200));
        $manilaNow = now('Asia/Manila');
        $createdToday = User::query()
            ->where('role', 'student')
            ->whereBetween('created_at', [
                $manilaNow->copy()->startOfDay()->utc(),
                $manilaNow->copy()->endOfDay()->utc(),
            ])->count();

        return [
            'dailyAccountLimit' => $limit,
            'accountsCreatedToday' => $createdToday,
            'remainingAccounts' => max(0, $limit - $createdToday),
        ];
    }
}
