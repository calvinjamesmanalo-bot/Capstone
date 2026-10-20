<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentAccountActivation;
use App\Models\StudentMasterlistImport;
use App\Models\User;
use App\Notifications\StudentIdentityApproved;
use App\Support\StudentMasterlistPdfParser;
use App\Support\TemporaryStudentPassword;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class StudentMasterlistController extends Controller
{
    public function accountVerifications(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:pending,approved,rejected,completed'],
        ]);
        $search = trim($validated['search'] ?? '');
        $status = $validated['status'] ?? 'pending';

        $verifications = Student::query()
            ->whereNotNull('account_verification_status')
            ->when($status !== '', fn ($query) => $query->where('account_verification_status', $status))
            ->when($search !== '', function ($query) use ($search): void {
                $like = '%'.strtolower($search).'%';
                $query->where(fn ($nested) => $nested
                    ->whereRaw('LOWER(student_number) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(COALESCE(account_verification_email, ?)) LIKE ?', ['', $like]));
            })
            ->orderByRaw("CASE account_verification_status WHEN 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('updated_at')
            ->paginate(25)
            ->withQueryString();

        return view('account-verifications.index', [
            'verifications' => $verifications,
            'selectedStatus' => $status,
            'dailyAccountLimit' => $dailyAccountLimit = max(1, (int) config('security.student_registration.daily_account_limit', 200)),
            'accountsCreatedToday' => $accountsCreatedToday = User::query()
                ->where('role', 'student')
                ->whereBetween('created_at', [
                    now('Asia/Manila')->startOfDay()->utc(),
                    now('Asia/Manila')->endOfDay()->utc(),
                ])->count(),
            'remainingAccounts' => max(0, $dailyAccountLimit - $accountsCreatedToday),
            'counts' => Student::query()
                ->whereNotNull('account_verification_status')
                ->selectRaw('account_verification_status, COUNT(*) as total')
                ->groupBy('account_verification_status')
                ->pluck('total', 'account_verification_status'),
        ]);
    }

    public function proof(Student $student, string $type)
    {
        abort_unless(in_array($type, ['school-id', 'selfie'], true), 404);
        $path = $type === 'school-id' ? $student->school_id_path : $student->school_id_selfie_path;
        abort_unless(is_string($path) && Storage::disk('local')->exists($path), 404);

        return response()->file(Storage::disk('local')->path($path), [
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function approveIdentity(Student $student): RedirectResponse
    {
        abort_unless($student->account_verification_status === 'pending' && filled($student->account_verification_email), 422);
        $hours = max(1, (int) config('security.student_registration.identity_approval_hours', 72));

        $approvalToken = Str::random(64);

        try {
            Notification::route('mail', $student->account_verification_email)->notify(
                new StudentIdentityApproved(route('student.registration.continue', ['token' => $approvalToken]))
            );
        } catch (Throwable $exception) {
            report($exception);
            return back()->withErrors(['approval' => 'The approval email could not be sent, so the request was not approved. Please try again.']);
        }

        $student->forceFill([
            'account_verification_status' => 'approved',
            'account_verification_token_hash' => hash('sha256', $approvalToken),
            'account_verification_expires_at' => now()->addHours($hours),
            'account_verification_reviewed_by' => auth()->id(),
            'account_verification_reviewed_at' => now(),
            'account_verification_notes' => null,
        ])->save();

        record_log('Approved Student Account Identity', 'Students', "Approved School ID verification for Student Number {$student->student_number}.");

        return back()->with('success', 'Identity approved. The account-creation link was emailed to the student.');
    }

    public function rejectIdentity(Request $request, Student $student): RedirectResponse
    {
        abort_unless($student->account_verification_status === 'pending', 422);
        $validated = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        Storage::disk('local')->delete(array_filter([$student->school_id_path, $student->school_id_selfie_path]));
        $student->forceFill([
            'account_verification_status' => 'rejected',
            'school_id_path' => null,
            'school_id_selfie_path' => null,
            'account_verification_reviewed_by' => auth()->id(),
            'account_verification_reviewed_at' => now(),
            'account_verification_notes' => $validated['reason'],
        ])->save();
        record_log('Rejected Student Account Identity', 'Students', "Rejected School ID verification for Student Number {$student->student_number}.");

        return back()->with('success', 'Identity verification rejected. The uploaded ID photos were deleted.');
    }

    public function removeApprovedAccount(Request $request, Student $student): RedirectResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);
        $request->validate(['confirm_removal' => ['accepted']]);
        abort_unless(in_array($student->account_verification_status, ['approved', 'completed'], true), 422);

        $studentNumber = $student->student_number;
        $reservedEmail = Str::lower(trim((string) ($student->account_verification_email ?: $student->official_email)));

        DB::transaction(function () use ($student, $reservedEmail): void {
            $lockedStudent = Student::query()->lockForUpdate()->findOrFail($student->student_number);
            $user = User::query()
                ->where('role', 'student')
                ->where(function ($query) use ($lockedStudent, $reservedEmail): void {
                    $query->where('student_number', $lockedStudent->student_number);
                    if ($reservedEmail !== '') {
                        $query->orWhereRaw('LOWER(email) = ?', [$reservedEmail]);
                    }
                })
                ->lockForUpdate()
                ->first();

            Storage::disk('local')->delete(array_filter([
                $lockedStudent->school_id_path,
                $lockedStudent->school_id_selfie_path,
            ]));

            StudentAccountActivation::query()
                ->where('student_number', $lockedStudent->student_number)
                ->when($reservedEmail !== '', fn ($query) => $query->orWhereRaw('LOWER(email) = ?', [$reservedEmail]))
                ->delete();

            if ($user !== null) {
                DB::table(config('session.table', 'sessions'))->where('user_id', $user->getKey())->delete();
                $user->delete();
            }

            if ($reservedEmail !== '') {
                DB::table(config('auth.passwords.users.table', 'password_reset_tokens'))
                    ->whereRaw('LOWER(email) = ?', [$reservedEmail])
                    ->delete();
            }

            $lockedStudent->forceFill([
                'official_email' => null,
                'account_verification_status' => null,
                'account_verification_email' => null,
                'school_id_path' => null,
                'school_id_selfie_path' => null,
                'account_verification_token_hash' => null,
                'account_verification_expires_at' => null,
                'account_verification_reviewed_by' => null,
                'account_verification_reviewed_at' => null,
                'account_verification_notes' => null,
            ])->save();
        });

        record_log('Removed Approved Student Account', 'Account Verification', "Removed account verification and released Gmail for Student Number {$studentNumber}.");

        return redirect()->route('account-verifications.index', ['status' => 'approved'])
            ->with('success', 'The approved account was removed. Its Gmail address can now be used again.');
    }

    public function index(Request $request): View
    {
        return view('student-masterlist.index', [
            'imports' => StudentMasterlistImport::with('importer')->latest()->take(10)->get(),
            'total' => Student::whereNotNull('masterlist_import_id')->whereHas('user')->count(),
            'missingLrn' => Student::whereNotNull('masterlist_import_id')->whereHas('user')->whereNull('lrn')->count(),
        ]);
    }

    public function accounts(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $students = Student::query()
            ->with('user')
            ->whereNotNull('masterlist_import_id')
            ->whereHas('user')
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.strtolower($search).'%';
                $query->where(fn ($nested) => $nested
                    ->whereRaw('LOWER(student_number) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(COALESCE(lrn, ?)) LIKE ?', ['', $like])
                    ->orWhereRaw('LOWER(name) LIKE ?', [$like]));
            })
            ->when($request->filled('school_year'), fn ($query) => $query->where('masterlist_school_year', $request->query('school_year')))
            ->when($request->filled('campus'), fn ($query) => $query->where('masterlist_campus', $request->query('campus')))
            ->when($request->filled('grade_level'), fn ($query) => $query->where('masterlist_grade_level', $request->query('grade_level')))
            ->orderBy('masterlist_campus')->orderBy('masterlist_grade_level')->orderBy('masterlist_section')->orderBy('name')
            ->paginate(50)->withQueryString();

        return view('student-masterlist.accounts', [
            'students' => $students,
            'schoolYears' => Student::whereNotNull('masterlist_import_id')->whereNotNull('masterlist_school_year')->distinct()->orderByDesc('masterlist_school_year')->pluck('masterlist_school_year'),
            'campuses' => Student::whereNotNull('masterlist_import_id')->whereNotNull('masterlist_campus')->distinct()->orderBy('masterlist_campus')->pluck('masterlist_campus'),
            'gradeLevels' => Student::whereNotNull('masterlist_import_id')->whereNotNull('masterlist_grade_level')->distinct()->orderBy('masterlist_grade_level')->pluck('masterlist_grade_level'),
        ]);
    }

    public function store(Request $request, StudentMasterlistPdfParser $parser, TemporaryStudentPassword $passwords): RedirectResponse
    {
        $validated = $request->validate([
            'masterlist_pdf' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        $file = $validated['masterlist_pdf'];
        $hash = hash_file('sha256', $file->getRealPath());
        if (StudentMasterlistImport::where('file_sha256', $hash)->exists()) {
            throw ValidationException::withMessages(['masterlist_pdf' => 'This exact masterlist PDF has already been imported.']);
        }

        try {
            $parsed = $parser->parse($file->getRealPath());
        } catch (Throwable $exception) {
            throw ValidationException::withMessages(['masterlist_pdf' => $exception->getMessage()]);
        }

        $storagePath = $file->storeAs('student-masterlists', Str::uuid().'.pdf', 'local');
        try {
            [$import, $invalidLrn, $newRows, $reusedRows, $accountsCreated, $accountsSkipped, $credentials] = DB::transaction(function () use ($parsed, $file, $hash, $storagePath, $passwords) {
                $import = StudentMasterlistImport::create([
                    'school_year' => $parsed['school_year'],
                    'original_name' => $file->getClientOriginalName(),
                    'file_sha256' => $hash,
                    'storage_path' => $storagePath,
                    'mime_type' => $file->getMimeType(),
                    'imported_by' => auth()->id(),
                ]);

                Student::where('masterlist_school_year', $parsed['school_year'])->update(['masterlist_active' => false]);
                $invalidLrn = 0;
                $newRows = 0;
                $reusedRows = 0;
                $accountsCreated = 0;
                $accountsSkipped = 0;
                $credentials = [];
                foreach ($parsed['rows'] as $row) {
                    $student = Student::find($row['student_number']);
                    $student ? $reusedRows++ : $newRows++;

                    $lrn = $row['lrn'];
                    if ($lrn !== null && Student::where('lrn', $lrn)->where('student_number', '!=', $row['student_number'])->exists()) {
                        $lrn = null;
                    }
                    if ($lrn === null) {
                        $invalidLrn++;
                    }

                    $student ??= new Student(['student_number' => $row['student_number']]);
                    if (! $student->exists) {
                        $student->name = $row['name'];
                        $student->lrn = $lrn;
                    } elseif ($student->lrn === null && $lrn !== null) {
                        // Preserve the existing student/account record, but safely fill a missing LRN.
                        $student->lrn = $lrn;
                    }

                    // Existing students are reused, not duplicated. Only their current
                    // school-year placement is refreshed by the new official list.
                    $student->fill([
                        'masterlist_import_id' => $import->id,
                        'masterlist_school_year' => $parsed['school_year'],
                        'masterlist_campus' => $row['campus'],
                        'masterlist_grade_level' => $row['grade_level'],
                        'masterlist_section' => $row['section'],
                        'gender' => $row['gender'],
                        'masterlist_active' => true,
                    ])->save();

                    if (User::where('student_number', $row['student_number'])->exists()) {
                        $accountsSkipped++;
                        continue;
                    }

                    $temporaryPassword = $passwords->generate();
                    $accountEmail = strtolower($row['student_number']).'@student.fiatlux.local';
                    $student->official_email = $accountEmail;
                    $student->account_verification_status = 'completed';
                    $student->save();
                    User::create([
                        'name' => $this->accountStorageName($row['name']),
                        'email' => $accountEmail,
                        'email_verified_at' => now(),
                        'password' => $this->hashBulkTemporaryPassword($temporaryPassword),
                        'must_change_password' => true,
                        'role' => 'student',
                        'student_number' => $row['student_number'],
                    ]);
                    $credentials[] = [
                        'name' => $row['name'],
                        'student_number' => $row['student_number'],
                        'temporary_password' => $temporaryPassword,
                    ];
                    $accountsCreated++;
                }

                $import->update([
                    'imported_rows' => count($parsed['rows']),
                    'new_rows' => $newRows,
                    'reused_rows' => $reusedRows,
                    'invalid_lrn_rows' => $invalidLrn,
                ]);

                return [$import, $invalidLrn, $newRows, $reusedRows, $accountsCreated, $accountsSkipped, $credentials];
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($storagePath);
            throw $exception;
        }

        record_log('Created Student Accounts from PDF', 'Account Creation', "Processed {$import->imported_rows} students for SY {$import->school_year}; {$accountsCreated} accounts created and {$accountsSkipped} existing accounts skipped.");

        return redirect()->route('account-creation.index')
            ->with('success', "Masterlist processed. {$accountsCreated} student account(s) created; {$accountsSkipped} existing account(s) skipped without changes.")
            ->with('masterlist_account_results', [
                'processed' => $import->imported_rows,
                'created' => $accountsCreated,
                'skipped' => $accountsSkipped,
                'failed' => 0,
                'needs_lrn' => $invalidLrn,
                'credentials' => $credentials,
            ]);
    }

    public function reissueTemporaryPassword(Student $student, TemporaryStudentPassword $passwords): RedirectResponse
    {
        abort_unless(in_array(auth()->user()?->role, ['admin', 'registrar'], true), 403);
        $user = User::query()->where('role', 'student')->where('student_number', $student->student_number)->firstOrFail();
        $temporaryPassword = $passwords->generate();

        DB::transaction(function () use ($user, $temporaryPassword): void {
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->getKey())->delete();
            $user->forceFill([
                'password' => Hash::make($temporaryPassword),
                'must_change_password' => true,
            ])->save();
        });

        record_log('Reissued Student Temporary Password', 'Account Security', "Reissued temporary access for Student Number {$student->student_number}.");

        return back()->with('success', 'A new one-time temporary password was issued. All older sessions and passwords were invalidated.')
            ->with('reissued_student_credential', [
                'name' => $student->name,
                'student_number' => $student->student_number,
                'temporary_password' => $temporaryPassword,
            ]);
    }

    public function destroyGeneratedAccount(Request $request, Student $student): RedirectResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);
        $request->validate(['confirm_delete' => ['accepted']]);
        $user = User::query()->where('role', 'student')->where('student_number', $student->student_number)->firstOrFail();

        DB::transaction(function () use ($user): void {
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->getKey())->delete();
            $user->forceFill(['role' => 'archived_student', 'remember_token' => null])->save();
        });

        record_log('Archived Generated Student Account', 'Account Creation', "Archived the generated login for Student Number {$student->student_number}.");

        return back()->with('success', 'Student account moved to Archived Accounts for 6 months. Academic records were not removed.');
    }

    public function purgeAccountCreationData(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);
        $request->validate(['confirm_purge' => ['accepted']]);
        $paths = StudentMasterlistImport::query()->pluck('storage_path')->filter()->all();
        $generatedUsers = User::query()->whereIn('role', ['student', 'archived_student'])->whereRaw('LOWER(email) LIKE ?', ['%@student.fiatlux.local'])->get();

        DB::transaction(function () use ($generatedUsers): void {
            foreach ($generatedUsers as $user) {
                DB::table(config('session.table', 'sessions'))->where('user_id', $user->getKey())->delete();
                $user->delete();
            }
            Student::query()->whereNotNull('masterlist_import_id')->update([
                'masterlist_import_id' => null,
                'masterlist_school_year' => null,
                'masterlist_campus' => null,
                'masterlist_grade_level' => null,
                'masterlist_section' => null,
                'gender' => null,
                'masterlist_active' => false,
            ]);
            Student::query()->whereRaw('LOWER(COALESCE(official_email, ?)) LIKE ?', ['', '%@student.fiatlux.local'])
                ->update(['official_email' => null, 'account_verification_status' => null]);
            StudentMasterlistImport::query()->delete();
        });
        Storage::disk('local')->delete($paths);

        record_log('Deleted Account Creation Uploads', 'Account Creation', 'Deleted all uploaded roster PDFs, import history, and system-generated student accounts without deleting academic records.');

        return redirect()->route('account-creation.index')->with('success', 'All uploaded account-creation data and generated accounts were deleted. Academic records and grades were kept.');
    }

    private function hashBulkTemporaryPassword(string $password): string
    {
        // Bulk imports can create hundreds of one-time credentials in one HTTP
        // request. Keep Argon2id, but use its interactive-login baseline here so
        // shared hosting finishes before the reverse-proxy timeout. Laravel will
        // automatically upgrade the hash after the student's first successful
        // login, before the required permanent-password change.
        $driver = (string) config('hashing.driver', 'argon2id');
        $hash = in_array($driver, ['bcrypt', '2y'], true)
            ? password_hash($password, PASSWORD_BCRYPT, ['cost' => 8])
            : password_hash($password, PASSWORD_ARGON2ID, [
                'memory_cost' => 8192,
                'time_cost' => 1,
                'threads' => 1,
            ]);

        if (! is_string($hash)) {
            throw new \RuntimeException('Unable to secure the generated temporary password.');
        }

        return $hash;
    }

    private function accountStorageName(string $name): string
    {
        $name = trim($name);
        if (str_contains($name, ',')) {
            [$lastName, $givenNames] = array_pad(array_map('trim', explode(',', $name, 2)), 2, '');
        } else {
            $parts = preg_split('/\s+/u', $name) ?: [];
            $lastName = array_pop($parts) ?: $name;
            $givenNames = implode(' ', $parts);
        }

        return $lastName.',,'.$givenNames.',';
    }
}
