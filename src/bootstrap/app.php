<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Illuminate\Database\QueryException;

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

                // 1) Validation / Not found / Auth cases (keep your existing handlers)
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

                // 2) Show FULL SQL details for DB errors in local/testing
                if ($e instanceof QueryException) {
                    // Only dump details in dev-like envs
                    if (app()->environment(['local','testing']) || config('app.debug')) {
                        return response()->json([
                            'message'    => $e->getMessage(),              // high-level message
                            'exception'  => class_basename($e),
                            'sql'        => $e->getSql(),                  // SQL attempted
                            'bindings'   => $e->getBindings(),             // params
                            'previous'   => optional($e->getPrevious())->getMessage(), // driver error
                            'file'       => $e->getFile(),
                            'line'       => $e->getLine(),
                        ], 500);
                    }

                    // In prod: generic
                    return response()->json([
                        'message' => 'Database error',
                    ], 500);
                }

                // 3) Verbose fallback in local/testing; generic in prod
                if (app()->environment(['local','testing']) || config('app.debug')) {
                    return response()->json([
                        'message'   => $e->getMessage(),
                        'exception' => class_basename($e),
                        'file'      => $e->getFile(),
                        'line'      => $e->getLine(),
                        // keep trace short to avoid massive payloads
                        'trace'     => collect($e->getTrace())->take(5),
                    ], 500);
                }

                // Existing minimal fallback for prod
                return response()->json([
                    'message'   => 'Server error',
                    'exception' => config('app.debug') ? class_basename($e) : null,
                ], 500);
            }
        });
    })
    ->create();
