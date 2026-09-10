<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function loginAsRole($role)
    {
        // Simulate login by finding or creating a user with that role
        $user = User::where('role', $role)->first();

        if (! $user) {
            $name = ucfirst(str_replace('_', ' ', $role)).' User';
            $studentNumber = null;

            if ($role === 'student') {
                $name = 'Louisse Chua';
                $studentNumber = '2023-0001';
            }

            $user = User::create([
                'name' => $name,
                'email' => $role.'@example.com',
                'password' => bcrypt('password'),
                'role' => $role,
                'student_number' => $studentNumber,
            ]);
        } elseif ($role === 'student' && $user->name !== 'Louisse Chua') {
            // Update existing student user for testing
            $user->update([
                'name' => 'Louisse Chua',
                'student_number' => '2023-0001',
            ]);
        }

        Auth::login($user);

        session(['user_role' => $role]);

        return redirect()->route('dashboard')->with('success', 'Logged in as '.ucfirst(str_replace('_', ' ', $role)));
    }

    public function logout()
    {
        Auth::logout();
        session()->forget('user_role');

        return redirect()->route('login');
    }
}
