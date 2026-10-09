<?php

use App\Http\Middleware\CheckOperatorPermission;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Env;

// Baca .env hanya lewat $_SERVER/$_ENV (per request). Pada PHP thread-safe (mis. mod_php
// Apache di Laragon/Windows) putenv() dibagi antar-request sehingga .env kadang "hilang"
// (MissingAppKeyException acak) saat ada beberapa request bersamaan.
Env::disablePutenv();

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Register role middleware alias
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'operator.permission' => CheckOperatorPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
