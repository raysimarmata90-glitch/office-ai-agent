<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PasswordResetRequest;
use App\Models\User;
use Illuminate\Http\Request;

class ForgotPasswordController extends Controller
{
    public function showLinkRequestForm()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLinkEmail(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ], [
            'email.required' => 'Email harus diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.exists' => 'Email tidak terdaftar dalam sistem.',
            'reason.required' => 'Alasan harus diisi.',
            'reason.min' => 'Alasan minimal 10 karakter.',
            'reason.max' => 'Alasan maksimal 500 karakter.',
        ]);

        $user = User::where('email', $validated['email'])->first();

        // Check if there's already a pending request
        $existingRequest = PasswordResetRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if ($existingRequest) {
            return back()->with('info', 'Permintaan reset password Anda masih dalam proses. Silakan tunggu admin untuk memproses permintaan Anda.');
        }

        // Create new request
        PasswordResetRequest::create([
            'user_id' => $user->id,
            'email' => $validated['email'],
            'reason' => $validated['reason'],
            'status' => 'pending',
        ]);

        return back()->with('success', 'Permintaan reset password berhasil dikirim. Admin akan segera memproses permintaan Anda.');
    }
}
