<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// Redirect root to register
Route::get('/', function () {
    if (auth()->check()) {
        if (auth()->user()->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }
        return redirect()->route('dashboard');
    }
    return redirect()->route('register');
});

// Auth Routes
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);

    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // User Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/pekerjaan', [DashboardController::class, 'pekerjaan'])->name('pekerjaan.index');

    // Chat Routes
    Route::post('/conversations/start', [ChatController::class, 'startConversation'])->name('conversations.start');
    Route::get('/conversations/{conversation}', [ChatController::class, 'show'])->name('chat.show');
    Route::post('/conversations/{conversation}/messages', [ChatController::class, 'sendMessage'])->name('chat.send');
    Route::get('/conversations/{conversation}/messages', [ChatController::class, 'getMessages'])->name('chat.messages');
    Route::delete('/conversations/{conversation}', [ChatController::class, 'destroy'])->name('conversations.destroy');
});

// Admin Routes
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::patch('/pekerjaan/{pekerjaan}', [AdminDashboardController::class, 'updatePekerjaan'])->name('pekerjaan.update');
    Route::get('/users', [AdminDashboardController::class, 'users'])->name('users');
    Route::post('/users/{user}/toggle-status', [AdminDashboardController::class, 'toggleUserStatus'])->name('users.toggle-status');
    Route::get('/conversations', [AdminDashboardController::class, 'conversations'])->name('conversations');
    Route::get('/chat-histories', [AdminDashboardController::class, 'chatHistories'])->name('chat.histories');
    Route::get('/pekerjaan', [AdminDashboardController::class, 'pekerjaan'])->name('pekerjaan');
    Route::get('/conversations/{conversation}', [AdminDashboardController::class, 'conversationDetail'])->name('conversation.detail');
});

