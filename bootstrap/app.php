<?php

use App\Http\Middleware\EnsureRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up'
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Dibutuhkan ketika aplikasi dibuka melalui ngrok/reverse proxy agar
        // Laravel mengenali scheme HTTPS dan host publik dari forwarded headers.
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'role' => EnsureRole::class,
        ]);

        $middleware->redirectGuestsTo('/login');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Exception khusus aplikasi ditangani pada controller/middleware terkait.
    })
    ->create();
