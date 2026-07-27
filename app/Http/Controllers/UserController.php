<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;
use App\Models\Student;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $staffUsers = User::where('role', '!=', 'student')
            ->orderBy('role')
            ->orderBy('name')
            ->get();

        $studentUsers = User::where('role', 'student')
            ->orderBy('name')
            ->orderBy('student_number')
            ->get();

        return view('users.index', compact('staffUsers', 'studentUsers'));
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
            'student_number' => 'nullable|string|max:50|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|string|in:admin,registrar,records_officer,student',
        ]);

        // Internal Storage Format: Last, Middle, First, Suffix
        // We use a pipe or specific delimiter if comma is risky, 
        // but since we already started with comma, let's make it consistent
        $name = trim($validated['last_name']) . ',' . 
                trim($validated['middle_name'] ?? '') . ',' . 
                trim($validated['first_name']) . ',' . 
                trim($validated['suffix'] ?? '');

        $user = User::create([
            'name' => $name,
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'student_number' => $validated['role'] === 'student' ? $validated['student_number'] : null,
        ]);

        // If student, ensure the students table is also updated/created
        if ($user->role === 'student' && $user->student_number) {
            Student::updateOrCreate(
                ['student_number' => $user->student_number],
                ['name' => $user->display_name] // Use First Middle Last for display
            );
        }

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
            'student_number' => 'nullable|string|max:50|unique:users,student_number,' . $user->id,
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|string|in:admin,registrar,records_officer,student',
        ]);

        // Internal Format: Last, Middle, First, Suffix
        $name = trim($validated['last_name']) . ',' . 
                trim($validated['middle_name'] ?? '') . ',' . 
                trim($validated['first_name']) . ',' . 
                trim($validated['suffix'] ?? '');

        $oldStudentNumber = $user->student_number;

        $data = [
            'name' => $name,
            'email' => $validated['email'],
            'role' => $validated['role'],
            'student_number' => $validated['role'] === 'student' ? $validated['student_number'] : null,
        ];

        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);

        // If student, sync with students table
        if ($user->role === 'student' && $user->student_number) {
            // If the student number itself was changed, we must update the existing record 
            // matching the old student number to avoid creating a duplicate or failing unique constraint
            if ($oldStudentNumber && $oldStudentNumber !== $user->student_number) {
                $existingStudent = Student::where('student_number', $oldStudentNumber)->first();
                if ($existingStudent) {
                    $existingStudent->update([
                        'student_number' => $user->student_number,
                        'name' => $user->display_name
                    ]);
                } else {
                    // Fallback if no old record found (should not happen if data is consistent)
                    Student::updateOrCreate(
                        ['student_number' => $user->student_number],
                        ['name' => $user->display_name]
                    );
                }
            } else {
                // If student number didn't change, just update the name
                Student::updateOrCreate(
                    ['student_number' => $user->student_number],
                    ['name' => $user->display_name]
                );
            }
        }

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
            'can_bypass_request_limit' => !$user->can_bypass_request_limit
        ]);

        $status = $user->can_bypass_request_limit ? 'enabled' : 'disabled';
        record_log('Toggled Request Bypass', 'User Management', "{$status} re-request bypass for {$user->display_name}");

        return redirect()->back()->with('success', "Re-request capability has been " . ($user->can_bypass_request_limit ? 'enabled' : 'disabled') . " for this student.");
    }
}
