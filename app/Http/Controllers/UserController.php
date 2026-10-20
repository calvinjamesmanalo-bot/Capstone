<?php

namespace App\Http\Controllers;

use App\Models\SchoolFormStudent;
use App\Models\Student;
use App\Models\StudentAccountActivation;
use App\Models\User;
use App\Support\DemoStudentData;
use App\Support\TemporaryStudentPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $this->purgeExpiredArchives();
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
        ]);
        $search = trim($validated['search'] ?? '');
        $schoolFormStudentNumbers = [];
        if ($search !== '') {
            try {
                $schoolFormStudentNumbers = SchoolFormStudent::query()
                    ->whereLike('lrn', "%{$search}%")
                    ->pluck('student_number')
                    ->filter()
                    ->all();
            } catch (\Throwable) {
                // The main account directory still works if the separate
                // school-forms database is unavailable.
            }
        }

        $matching = function ($query) use ($search, $schoolFormStudentNumbers): void {
            if ($search === '') {
                return;
            }

            $query->where(function ($query) use ($search, $schoolFormStudentNumbers): void {
                $query->whereLike('email', "%{$search}%")
                    ->orWhereLike('student_number', "%{$search}%")
                    ->orWhereLike('name', "%{$search}%")
                    ->orWhereHas('student', fn ($student) => $student->whereLike('lrn', "%{$search}%"));

                if ($schoolFormStudentNumbers !== []) {
                    $query->orWhereIn('student_number', $schoolFormStudentNumbers);
                }
            });
        };

        $staffUsers = User::query()
            ->where('role', '!=', 'student')
            ->where('role', 'not like', 'archived_%')
            ->when($search !== '', $matching)
            ->orderBy('role')
            ->orderBy('name')
            ->get();

        $studentUsers = User::query()
            ->where('role', 'student')
            ->when($search !== '', $matching)
            ->with('student')
            ->orderBy('name')
            ->orderBy('student_number')
            ->get();

        $archivedUsers = User::query()
            ->where('role', 'like', 'archived_%')
            ->when($search !== '', $matching)
            ->orderByDesc('updated_at')
            ->get();

        return view('users.index', compact('staffUsers', 'studentUsers', 'archivedUsers', 'search'));
    }

    public function create()
    {
        return view('users.create');
    }

    public function issueTemporaryPassword(User $user, TemporaryStudentPassword $passwords)
    {
        abort_unless(in_array(auth()->user()?->role, ['admin', 'registrar'], true), 403);
        abort_unless($user->role === 'student' && filled($user->student_number), 422, 'Temporary passwords can be issued only to active student accounts.');

        $temporaryPassword = $passwords->generate();
        DB::transaction(function () use ($user, $temporaryPassword): void {
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->getKey())->delete();
            $user->forceFill([
                'password' => Hash::make($temporaryPassword),
                'must_change_password' => true,
                'remember_token' => null,
            ])->save();
        });

        record_log(
            'Issued Random Student Temporary Password',
            'Account Security',
            "Issued one-time temporary access for Student Number {$user->student_number}; issued by ".auth()->user()->role.'.'
        );

        return redirect()->route('users.index', ['search' => $user->student_number])
            ->with('success', 'A new random temporary password was issued. Give it directly to the student; it will not be shown again after leaving this page.')
            ->with('issued_student_credential', [
                'name' => $user->display_name,
                'student_number' => $user->student_number,
                'temporary_password' => $temporaryPassword,
            ]);
    }

    public function createDemoStudent(DemoStudentData $demo)
    {
        $user = $demo->create();
        record_log('Created Demo Student', 'User Management', 'Prepared the full Kinder-to-SHS demo history for '.$user->student_number.'.');

        return redirect()->route('users.index', ['search' => DemoStudentData::STUDENT_NUMBER])
            ->with('success', 'Calvin James Manalo demo account is ready with complete Kinder-to-Grade-12 grades and attendance.');
    }

    public function store(Request $request)
    {
        $request->merge([
            'student_number' => Str::upper(trim((string) $request->input('student_number'))),
            'lrn' => preg_replace('/\D/', '', (string) $request->input('lrn')),
        ]);

        $validated = $request->validate([
            'last_name' => 'required|string|max:100',
            'first_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'suffix' => 'nullable|string|max:10',
            'student_number' => [
                Rule::requiredIf(fn (): bool => $request->input('role') === 'student'),
                'nullable',
                'string',
                'max:50',
                'unique:users,student_number',
            ],
            'lrn' => [
                Rule::requiredIf(fn (): bool => $request->input('role') === 'student'),
                'nullable',
                'string',
                'regex:/^\d{12}$/',
                Rule::unique('students', 'lrn')->ignore($request->input('student_number'), 'student_number'),
            ],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(12)->max(64)->mixedCase()->numbers(), 'regex:/[^\pL\pN\s]/u'],
            'role' => 'required|string|in:admin,registrar,records_officer,student',
        ], $this->passwordValidationMessages());

        $validated['email'] = Str::lower(trim($validated['email']));

        // Internal Storage Format: Last, Middle, First, Suffix
        // We use a pipe or specific delimiter if comma is risky,
        // but since we already started with comma, let's make it consistent
        $name = trim($validated['last_name']).','.
                trim($validated['middle_name'] ?? '').','.
                trim($validated['first_name']).','.
                trim($validated['suffix'] ?? '');
        $student = $this->officialStudentFor($validated, $name, true);

        $user = DB::transaction(function () use ($validated, $student, $name): User {
            if ($student) {
                $student->fill([
                    'name' => $name,
                    'lrn' => $validated['lrn'],
                    'official_email' => $validated['email'],
                ])->save();
            }

            return User::create([
                'name' => $name,
                'email' => $validated['email'],
                'email_verified_at' => null,
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
                'student_number' => $validated['role'] === 'student' ? $validated['student_number'] : null,
            ]);
        });

        $verificationSent = true;
        try {
            $user->sendEmailVerificationNotification();
        } catch (\Throwable $exception) {
            $verificationSent = false;
            Log::warning('Verification email for an administrator-created account could not be sent.', ['exception' => $exception::class]);
        }

        record_log('Created User', 'User Management', "Created account for {$user->display_name} ({$user->role})");

        return redirect()->route('users.index')->with(
            $verificationSent ? 'success' : 'warning',
            $verificationSent
                ? 'User created. The student must verify their email before signing in.'
                : 'User created, but the verification email could not be sent. Configure SMTP, then resend verification.'
        );
    }

    public function edit(User $user)
    {
        // Internal Format: Last, Middle, First, Suffix
        $parts = array_map('trim', explode(',', $user->name));
        $user->last_name = $parts[0] ?? '';
        $user->middle_name = $parts[1] ?? '';
        $user->first_name = $parts[2] ?? '';
        $user->suffix = $parts[3] ?? '';

        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $request->merge([
            'lrn' => preg_replace('/\D/', '', (string) $request->input('lrn')),
        ]);

        $validated = $request->validate([
            'last_name' => 'required|string|max:100',
            'first_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'suffix' => 'nullable|string|max:10',
            'student_number' => [
                Rule::requiredIf(fn (): bool => $request->input('role') === 'student'),
                'nullable',
                'string',
                'max:50',
                Rule::unique('users', 'student_number')->ignore($user->id),
            ],
            'lrn' => [
                Rule::requiredIf(fn (): bool => $request->input('role') === 'student'),
                'nullable',
                'string',
                'regex:/^\d{12}$/',
                Rule::unique('students', 'lrn')->ignore($user->student_number, 'student_number'),
            ],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Password::min(12)->max(64)->mixedCase()->numbers(), 'regex:/[^\pL\pN\s]/u'],
            'role' => 'required|string|in:admin,registrar,records_officer,student',
        ], $this->passwordValidationMessages());

        $validated['email'] = Str::lower(trim($validated['email']));

        // Internal Format: Last, Middle, First, Suffix
        $name = trim($validated['last_name']).','.
                trim($validated['middle_name'] ?? '').','.
                trim($validated['first_name']).','.
                trim($validated['suffix'] ?? '');
        $student = $validated['role'] === 'student'
            ? ($user->student ?: Student::find($validated['student_number']) ?: new Student)
            : null;

        $data = [
            'name' => $name,
            'email' => $validated['email'],
            'role' => $validated['role'],
            'student_number' => $validated['role'] === 'student' ? $validated['student_number'] : null,
        ];

        if (! empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        DB::transaction(function () use ($data, $student, $user, $validated): void {
            if ($student) {
                $student->fill([
                    'student_number' => $validated['student_number'],
                    'name' => $data['name'],
                    'lrn' => $validated['lrn'],
                    'official_email' => $validated['email'],
                ])->save();
            }

            $user->update($data);
        });

        record_log('Updated User', 'User Management', "Updated account details for {$user->display_name}");

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        abort_if($user->is(auth()->user()), 422, 'You cannot archive the account you are currently using.');
        if ($user->role === 'admin' && User::where('role', 'admin')->count() <= 1) {
            throw ValidationException::withMessages(['account' => 'The last active administrator cannot be archived.']);
        }
        $name = $user->display_name;
        DB::transaction(function () use ($user): void {
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->getKey())->delete();
            DB::table(config('auth.passwords.users.table', 'password_reset_tokens'))
                ->whereRaw('LOWER(email) = ?', [Str::lower($user->email)])
                ->delete();
            $user->forceFill([
                'role' => 'archived_'.$user->role,
                'remember_token' => null,
            ])->save();
        });

        record_log('Archived User', 'User Management', "Archived account for {$name}");

        return redirect()->route('users.index')->with('success', 'Account moved to Archived Accounts. It can be restored for 6 months.');
    }

    public function restore(int $id)
    {
        $user = User::query()->whereKey($id)->where('role', 'like', 'archived_%')->firstOrFail();
        $name = $user->display_name;
        $user->forceFill(['role' => $user->archivedOriginalRole()])->save();
        record_log('Restored User', 'User Management', "Restored account for {$name}");

        return redirect()->route('users.index')->with('success', 'Account restored and can sign in again.');
    }

    public function forceDestroy(int $id)
    {
        $user = User::query()->whereKey($id)->where('role', 'like', 'archived_%')->firstOrFail();
        $name = $user->display_name;
        $this->permanentlyDelete($user);
        record_log('Permanently Deleted User', 'User Management', "Permanently deleted archived account for {$name}");

        return redirect()->route('users.index')->with('success', 'Archived account permanently deleted.');
    }

    public function purgeExpiredArchives(): void
    {
        User::query()
            ->where('role', 'like', 'archived_%')
            ->where('updated_at', '<=', now()->subMonthsNoOverflow(6))
            ->eachById(fn (User $user) => $this->permanentlyDelete($user));
    }

    private function permanentlyDelete(User $user): void
    {
        DB::transaction(function () use ($user): void {
            if ($user->archivedOriginalRole() === 'student' && filled($user->student_number)) {
                $student = Student::query()->lockForUpdate()->find($user->student_number);
                if ($student !== null) {
                    Storage::disk('local')->delete(array_filter([$student->school_id_path, $student->school_id_selfie_path]));
                    StudentAccountActivation::query()->where('student_number', $student->student_number)
                        ->orWhereRaw('LOWER(email) = ?', [Str::lower($user->email)])->delete();
                    $student->forceFill([
                        'official_email' => null, 'account_verification_status' => null, 'account_verification_email' => null,
                        'school_id_path' => null, 'school_id_selfie_path' => null, 'account_verification_token_hash' => null,
                        'account_verification_expires_at' => null, 'account_verification_reviewed_by' => null,
                        'account_verification_reviewed_at' => null, 'account_verification_notes' => null,
                    ])->save();
                }
            }
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->getKey())->delete();
            DB::table(config('auth.passwords.users.table', 'password_reset_tokens'))->whereRaw('LOWER(email) = ?', [Str::lower($user->email)])->delete();
            $user->delete();
        });
    }

    public function toggleBypass(User $user)
    {
        abort_unless($user->role === 'student', 422, 'Re-request access can only be changed for student accounts.');

        $user->update([
            'can_bypass_request_limit' => ! $user->can_bypass_request_limit,
        ]);
        $user->refresh();

        $status = $user->can_bypass_request_limit ? 'enabled' : 'disabled';
        record_log('Toggled Request Bypass', 'User Management', "{$status} re-request bypass for {$user->display_name}");

        return redirect()->back()->with('success', 'Re-request capability has been '.($user->can_bypass_request_limit ? 'enabled' : 'disabled').' for this student.');
    }

    private function officialStudentFor(array $validated, ?string $name = null, bool $allowCreate = false): ?Student
    {
        if ($validated['role'] !== 'student') {
            return null;
        }

        $student = Student::find($validated['student_number']);
        if ($allowCreate) {
            return $student ?: new Student([
                'student_number' => $validated['student_number'],
                'name' => $name,
            ]);
        }

        if ($student === null) {
            throw ValidationException::withMessages([
                'student_number' => 'The selected student number is invalid.',
            ]);
        }
        $officialLrn = $student->resolvedLrn();
        if ($officialLrn !== null && ! hash_equals($officialLrn, $validated['lrn'])) {
            throw ValidationException::withMessages([
                'lrn' => 'The LRN must match the official student record.',
            ]);
        }

        try {
            $studentUsingLrn = SchoolFormStudent::query()->where('lrn', $validated['lrn'])->first();
            if ($studentUsingLrn !== null
                && ! hash_equals((string) $studentUsingLrn->student_number, $validated['student_number'])) {
                throw ValidationException::withMessages([
                    'lrn' => 'The LRN is assigned to a different student record.',
                ]);
            }
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable) {
            // Main roster validation remains authoritative when the integrated
            // school-forms database is unavailable.
        }

        if ($student->official_email !== null
            && ! hash_equals(Str::lower(trim($student->official_email)), $validated['email'])) {
            throw ValidationException::withMessages([
                'email' => 'The email must match the official email stored for this student number.',
            ]);
        }

        return $student;
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
