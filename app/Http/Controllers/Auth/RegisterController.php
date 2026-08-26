<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        // Only show specific departments for public registration
        // Exclude ops-legal and other restricted departments
        $departments = Department::whereIn('code', ['ai', 'platform', 'ba', 'td'])
            ->orderBy('name')
            ->get();
        return view('auth.register', compact('departments'));
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'department_id' => ['required', 'exists:departments,id'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $userRole = Role::where('name', 'user')->first();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role_id' => $userRole->id,
            'department_id' => $validated['department_id'],
            'phone' => $validated['phone'] ?? null,
        ]);

        Auth::login($user);

        return redirect()->route('dashboard')->with('success', 'Registrasi berhasil! Selamat datang.');
    }
}
