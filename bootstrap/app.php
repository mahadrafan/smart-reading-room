<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // halaman tamu (login, daftar, lupa password) untuk pengguna yang masih login, termasuk lewat "Ingat saya":
        // langsung ke landing page sesuai rolenya
        $middleware->redirectUsersTo(
            fn () => auth()->user()->role == 'Admin' ? route('admin.dashboard') : route('dashboard')
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
