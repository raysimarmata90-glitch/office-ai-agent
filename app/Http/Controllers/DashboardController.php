<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Department;
use App\Models\Pekerjaan;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Auto-redirect to existing active conversation or create new one
        $activeConversation = Conversation::where('user_id', $user->id)
            ->where('department_id', $user->department_id)
            ->where('status', 'active')
            ->latest('updated_at')
            ->first();

        if ($activeConversation) {
            return redirect()->route('chat.show', $activeConversation->id);
        }

        // Belum ada percakapan aktif: buka layar chat baru tanpa membuat record dulu.
        return redirect()->route('chat.baru');
    }

    public function pekerjaan()
    {
        $user = auth()->user();
        $pekerjaan = Pekerjaan::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('pekerjaan', compact('user', 'pekerjaan'));
    }
}
