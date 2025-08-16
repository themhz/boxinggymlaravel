<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;


return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        App\Providers\AuthServiceProvider::class,
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {

        // JSON response for API routes
        $exceptions->render(function (\Throwable $e, $request) {
            if ($request->is('api/*')) {

                if ($e instanceof ValidationException) {
                    return response()->json([
                        'message' => 'Validation failed',
                        'errors'  => $e->errors(),
                    ], 422);
                }

                if ($e instanceof ModelNotFoundException) {
                    return response()->json(['message' => 'Not found'], 404);
                }

                if ($e instanceof AuthenticationException) {
                    return response()->json(['message' => 'Unauthenticated'], 401);
                }

                if ($e instanceof AuthorizationException) {
                    return response()->json(['message' => 'Forbidden'], 403);
                }

                if ($e instanceof HttpExceptionInterface) {
                    return response()->json([
                        'message' => $e->getStatusCode() === 404 ? 'Not found' : $e->getMessage(),
                    ], $e->getStatusCode());
                }

                // Fallback for unexpected exceptions
                return response()->json([
                    'message'   => 'Server error',
                    'exception' => config('app.debug') ? class_basename($e) : null,
                ], 500);
            }
        });
    })
    ->create();
