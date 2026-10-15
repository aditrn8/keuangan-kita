<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'api/telegram/webhook',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->respond(function ($response, \Throwable $e, Request $request) {
            return response(
                "SERVERLESS DIAGNOSTIC EXCEPTION:\n" .
                $e->getMessage() . "\n\n" .
                $e->getFile() . ":" . $e->getLine() . "\n\n" .
                $e->getTraceAsString(),
                500,
                ['Content-Type' => 'text/plain; charset=utf-8']
            );
        });
    })->create();
