<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\ChatHistory;
use App\Models\Department;
use App\Models\Pekerjaan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $pekerjaan = Pekerjaan::orderBy('created_at', 'desc')
            ->paginate(30);

        return view('admin.dashboard', compact('user', 'pekerjaan'));
    }

    public function users()
    {
        $users = User::with(['role', 'department', 'conversations'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.users', compact('users'));
    }

    public function updatePekerjaan(Request $request, Pekerjaan $pekerjaan)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['on going', 'completed'])],
            'kategori' => ['required', Rule::in(['Highest', 'High', 'Medium', 'Low', 'Lowest'])],
        ]);

        $pekerjaan->update($validated);

        return back()->with('success', 'Status dan kategori pekerjaan berhasil diperbarui.');
    }

    public function toggleUserStatus(User $user)
    {
        if ($user->is(auth()->user())) {
            return back()->with('error', 'Akun admin yang sedang digunakan tidak dapat dinonaktifkan.');
        }

        $user->update(['is_active' => !$user->is_active]);

        return back()->with(
            'success',
            $user->is_active
                ? 'User berhasil diaktifkan.'
                : 'User berhasil dinonaktifkan.'
        );
    }

    public function conversations()
    {
        $conversations = Conversation::with(['user', 'department', 'messages'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.conversations', compact('conversations'));
    }

    public function chatHistories()
    {
        $histories = ChatHistory::with(['conversation', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate(30);

        return view('admin.chat-histories', compact('histories'));
    }

    public function pekerjaan()
    {
        $pekerjaan = Pekerjaan::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(30);

        return view('admin.pekerjaan', compact('pekerjaan'));
    }

    public function conversationDetail(Conversation $conversation)
    {
        $conversation->load(['user', 'department', 'messages']);
        return view('admin.conversation-detail', compact('conversation'));
    }
}
