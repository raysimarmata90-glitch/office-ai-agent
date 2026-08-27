<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\Admin\ProjectAdminController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\ProjectChatController;
use App\Http\Controllers\TaskController;
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

    // Forgot Password Routes
    Route::get('/forgot-password', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // User Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/pekerjaan', [TaskController::class, 'index'])->name('pekerjaan.index');

    // Tugas (user)
    Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::get('/tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');
    Route::patch('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
    Route::delete('/evidence/{evidence}', [TaskController::class, 'hapusEvidence'])->name('tasks.evidence.hapus');
    Route::patch('/tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.status');
    Route::get('/evidence/{evidence}', [TaskController::class, 'evidence'])->name('tasks.evidence');

    // Profil pengguna
    Route::get('/profil', [ProfilController::class, 'show'])->name('profil.show');
    Route::post('/profil', [ProfilController::class, 'update'])->name('profil.update');
    Route::delete('/profil/foto', [ProfilController::class, 'hapusFoto'])->name('profil.foto.hapus');
    Route::post('/profil/sandi', [ProfilController::class, 'sandi'])->name('profil.sandi');
    Route::delete('/profil/sesi/{sesi}', [ProfilController::class, 'keluarSesi'])->name('profil.sesi.keluar');

    // Chat Routes
    Route::get('/chat/baru', [ChatController::class, 'baru'])->name('chat.baru');
    Route::post('/conversations', [ChatController::class, 'mulai'])->name('chat.mulai');
    Route::get('/riwayat', [ChatController::class, 'riwayat'])->name('chat.riwayat');
    Route::post('/conversations/start', [ChatController::class, 'startConversation'])->name('conversations.start');
    Route::get('/conversations/{conversation}', [ChatController::class, 'show'])->name('chat.show');
    Route::post('/conversations/{conversation}/messages', [ChatController::class, 'sendMessage'])->name('chat.send');
    Route::get('/conversations/{conversation}/messages', [ChatController::class, 'getMessages'])->name('chat.messages');
    Route::delete('/conversations/{conversation}', [ChatController::class, 'destroy'])->name('conversations.destroy');

    // Project Chat Routes (Structured Flow)
    Route::prefix('project-chat')->name('project-chat.')->group(function () {
        Route::get('/', function() {
            return view('project-chat');
        })->name('index');
        Route::get('/init', [ProjectChatController::class, 'initSession'])->name('init');
        Route::post('/message', [ProjectChatController::class, 'handleMessage'])->name('message');
        Route::post('/reset', [ProjectChatController::class, 'resetSession'])->name('reset');
    });
});

// Admin Routes
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/users', [AdminDashboardController::class, 'users'])->name('users');
    Route::post('/users/{user}/toggle-status', [AdminDashboardController::class, 'toggleUserStatus'])->name('users.toggle-status');
    Route::get('/users/{user}', [AdminDashboardController::class, 'showUser'])->name('users.show');
    Route::patch('/users/{user}', [AdminDashboardController::class, 'updateUser'])->name('users.update');
    Route::delete('/users/{user}', [AdminDashboardController::class, 'destroyUser'])->name('users.destroy');
    Route::post('/users/{user}/sandi', [AdminDashboardController::class, 'sandiUser'])->name('users.sandi');
    Route::delete('/users/{user}/foto', [AdminDashboardController::class, 'hapusFotoUser'])->name('users.foto.hapus');
    Route::delete('/users/{user}/sesi/{sesi}', [AdminDashboardController::class, 'keluarSesiUser'])->name('users.sesi.keluar');
    Route::get('/conversations', [AdminDashboardController::class, 'conversations'])->name('conversations');
    Route::get('/pekerjaan', [AdminDashboardController::class, 'pekerjaan'])->name('pekerjaan');
    Route::get('/kanban', [AdminDashboardController::class, 'kanbanFeed'])->name('kanban');
    Route::get('/conversations/{conversation}', [AdminDashboardController::class, 'conversationDetail'])->name('conversation.detail');
    Route::get('/laporan', [AdminDashboardController::class, 'laporan'])->name('laporan');
    Route::get('/laporan/ekspor', [AdminDashboardController::class, 'ekspor'])->name('laporan.ekspor');

    // Password Reset Requests
    Route::get('/password-requests', [AdminDashboardController::class, 'passwordRequests'])->name('password-requests');
    Route::post('/password-requests/{request}/approve', [AdminDashboardController::class, 'approvePasswordRequest'])->name('password-requests.approve');
    Route::post('/password-requests/{request}/reject', [AdminDashboardController::class, 'rejectPasswordRequest'])->name('password-requests.reject');

    // Proyek
    Route::get('/proyek', [ProjectAdminController::class, 'index'])->name('proyek.index');
    Route::post('/proyek', [ProjectAdminController::class, 'store'])->name('proyek.store');
    Route::get('/proyek/{project}', [ProjectAdminController::class, 'show'])->name('proyek.show');
    Route::patch('/proyek/{project}/deliverables/percentages', [ProjectAdminController::class, 'updateDeliverablePercentages'])->name('proyek.deliverables.percentages');
    Route::delete('/proyek/{project}', [ProjectAdminController::class, 'destroy'])->name('proyek.destroy');

    // Blocked Projects Monitoring
    Route::get('/blocked-projects', [AdminDashboardController::class, 'blockedProjects'])->name('blocked-projects');
    Route::post('/blocked-projects/{projectId}/unblock', [AdminDashboardController::class, 'unblockProject'])->name('blocked-projects.unblock');
    Route::post('/blocked-projects/check', [AdminDashboardController::class, 'runOverdueCheck'])->name('blocked-projects.check');

    // Assign tugas ke pegawai
    Route::post('/tugas/assign', [ProjectAdminController::class, 'assign'])->name('tugas.assign');
    Route::patch('/tugas/{task}/status', [TaskController::class, 'updateStatus'])->name('tugas.status');
});

