<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function ($schedule) {
        // Check overdue projects every day at midnight
        $schedule->command('projects:check-overdue')
            ->daily()
            ->at('00:00')
            ->timezone('Asia/Jakarta');
        
        // Also check at noon for extra safety
        $schedule->command('projects:check-overdue')
            ->daily()
            ->at('12:00')
            ->timezone('Asia/Jakarta');
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Unggahan yang melebihi post_max_size PHP tidak sampai ke validator,
        // jadi diterjemahkan di sini agar user melihat pesan yang jelas.
        $exceptions->render(function (PostTooLargeException $e, Request $request) {
            $pesan = 'Total ukuran file yang dikirim melebihi batas server ('
                . ini_get('post_max_size') . '). Kurangi jumlah atau ukuran file lalu coba lagi.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $pesan], 413);
            }

            return redirect()->back()->withInput()->withErrors(['evidence' => $pesan]);
        });
    })->create();
