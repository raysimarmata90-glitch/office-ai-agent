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

        // Only show user's own department
        $departments = Department::where('id', $user->department_id)->get();

        // Only show conversations from user's department
        $conversations = Conversation::with(['department', 'messages'])
            ->where('user_id', $user->id)
            ->where('department_id', $user->department_id)
            ->orderBy('updated_at', 'desc')
            ->get();

        $pekerjaan = Pekerjaan::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('dashboard', compact('user', 'departments', 'conversations', 'pekerjaan'));
    }
}
