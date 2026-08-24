<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Department;
use App\Models\Message;
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

        // Create new conversation automatically
        $conversation = Conversation::create([
            'user_id' => $user->id,
            'department_id' => $user->department_id,
            'title' => 'New Chat',
            'status' => 'active',
            'current_step' => 1,
        ]);

        // Create welcome message
        Message::create([
            'conversation_id' => $conversation->id,
            'sender_type' => 'ai',
            'content' => 'Halo! Senang bertemu dengan Anda. Apa proyek yang sedang Anda kerjakan hari ini?',
            'step_number' => 1,
            'metadata' => [
                'system_prompt' => 'Sapa user dengan hangat, lalu gali proyek, objektif, harapan, task, dan estimasi durasi pengerjaan.',
            ],
        ]);

        return redirect()->route('chat.show', $conversation->id);
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
