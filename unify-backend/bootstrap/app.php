<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Route-level middleware aliases used across routes/api.php
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'reqlog' => \App\Http\Middleware\RequestLogger::class,
            // Post-audit F-01: server-side temp-password rotation gate.
            'password.changed' => \App\Http\Middleware\EnsurePasswordChanged::class,
        ]);

        $middleware->api([
            \Illuminate\Http\Middleware\HandleCors::class,
            // TODO-037: log requests that exceed the perf budget (perf channel).
            \App\Http\Middleware\MeasureRequestDuration::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();