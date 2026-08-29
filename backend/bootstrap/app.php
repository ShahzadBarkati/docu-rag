<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('api/*') ? null : route('login')
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontReportDuplicates();

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(fn (ValidationException $e, Request $request) => $request->is('api/*') || $request->expectsJson() ?
                response()->json([
                    'message' => $e->getMessage(),
                    'errors' => $e->errors(),
                ], $e->status) : null
        );

        $exceptions->render(fn (AuthenticationException $e, Request $request) => $request->is('api/*') || $request->expectsJson() ?
                response()->json([
                    'message' => $e->getMessage(),
                    'errors' => [],
                ], 401) : null
        );

        $exceptions->render(fn (HttpExceptionInterface $e, Request $request) => $request->is('api/*') || $request->expectsJson() ?
                response()->json([
                    'message' => $e->getMessage(),
                    'errors' => [],
                ], $e->getStatusCode()) : null
        );

        // Put it in the Last - common error handler
        $exceptions->render(fn (Throwable $e, Request $request) => $request->is('api/*') || $request->expectsJson() ? response()->json([
            'message' => 'Something went wrong.',
            'errors' => [],
        ], 500) : null
        );
    })->create();
