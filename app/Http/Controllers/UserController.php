<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
        ]);
        $search = trim($validated['search'] ?? '');

        $matching = function ($query) use ($search): void {
            if ($search === '') {
                return;
            }

            $query->where(function ($query) use ($search): void {
                $query->whereLike('email', "%{$search}%")
                    ->orWhereLike('student_number', "%{$search}%")
                    ->orWhereLike('name', "%{$search}%");
            });
        };

        $staffUsers = User::query()
            ->where('role', '!=', 'student')
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

        return view('users.index', compact('staffUsers', 'studentUsers', 'search'));
    }

    public function create()
    {
        return view('users.create');
    }

    public function store(Request $request)
    {
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
                Rule::exists('students', 'student_number'),
            ],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(12)->max(64)->mixedCase()->numbers(), 'regex:/[^\pL\pN\s]/u'],
            'role' => 'required|string|in:admin,registrar,records_officer,student',
        ], $this->passwordValidationMessages());

        $validated['email'] = Str::lower(trim($validated['email']));
        $student = $this->officialStudentFor($validated);

        // Internal Storage Format: Last, Middle, First, Suffix
        // We use a pipe or specific delimiter if comma is risky,
        // but since we already started with comma, let's make it consistent
        $name = trim($validated['last_name']).','.
                trim($validated['middle_name'] ?? '').','.
                trim($validated['first_name']).','.
                trim($validated['suffix'] ?? '');

        $user = DB::transaction(function () use ($validated, $student, $name): User {
            if ($student && $student->official_email === null) {
                $student->update(['official_email' => $validated['email']]);
            }

            return User::create([
                'name' => $name,
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
                'student_number' => $validated['role'] === 'student' ? $validated['student_number'] : null,
            ]);
        });

        $user->sendEmailVerificationNotification();

        record_log('Created User', 'User Management', "Created account for {$user->display_name} ({$user->role})");

        return redirect()->route('users.index')->with('success', 'User created successfully.');
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
                Rule::exists('students', 'student_number'),
            ],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Password::min(12)->max(64)->mixedCase()->numbers(), 'regex:/[^\pL\pN\s]/u'],
            'role' => 'required|string|in:admin,registrar,records_officer,student',
        ], $this->passwordValidationMessages());

        $validated['email'] = Str::lower(trim($validated['email']));
        $this->officialStudentFor($validated);

        if ($user->role === 'student' && ! hash_equals(Str::lower($user->email), $validated['email'])) {
            throw ValidationException::withMessages([
                'email' => 'Students must change their email through the verified Account Security process.',
            ]);
        }

        // Internal Format: Last, Middle, First, Suffix
        $name = trim($validated['last_name']).','.
                trim($validated['middle_name'] ?? '').','.
                trim($validated['first_name']).','.
                trim($validated['suffix'] ?? '');

        $data = [
            'name' => $name,
            'email' => $validated['email'],
            'role' => $validated['role'],
            'student_number' => $validated['role'] === 'student' ? $validated['student_number'] : null,
        ];

        if (! empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);

        record_log('Updated User', 'User Management', "Updated account details for {$user->display_name}");

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        $name = $user->display_name;
        $user->delete();

        record_log('Deleted User', 'User Management', "Deleted account for {$name}");

        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }

    public function toggleBypass(User $user)
    {
        $user->update([
            'can_bypass_request_limit' => ! $user->can_bypass_request_limit,
        ]);

        $status = $user->can_bypass_request_limit ? 'enabled' : 'disabled';
        record_log('Toggled Request Bypass', 'User Management', "{$status} re-request bypass for {$user->display_name}");

        return redirect()->back()->with('success', 'Re-request capability has been '.($user->can_bypass_request_limit ? 'enabled' : 'disabled').' for this student.');
    }

    private function officialStudentFor(array $validated): ?Student
    {
        if ($validated['role'] !== 'student') {
            return null;
        }

        $student = Student::findOrFail($validated['student_number']);
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
