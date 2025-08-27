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
                        'result' => 'error',
                        'message' => 'Validation failed',
                        'errors'  => $e->errors(),
                    ], 422);
                }

                if ($e instanceof ModelNotFoundException) {
                    $model = class_basename($e->getModel());
                    $ids   = $e->getIds(); // array|null

                    // Friendlier messages per model
                    $message = match ($model) {
                        'result' => 'error',
                        'StudentExercise' => 'Student exercise not found',
                        'Student'         => 'Student not found',
                        default           => "$model not found",
                    };

                    // In dev, include a hint
                    if (app()->environment(['local','testing']) || config('app.debug')) {
                        return response()->json([
                            'result' => 'error',
                            'message' => $message,
                            'model'   => $model,
                            'id'      => $ids,
                        ], 404);
                    }

                    return response()->json(['result' => 'error', 'message' => $message], 404);
                }

                if ($e instanceof AuthenticationException) {
                    return response()->json(['result' => 'error','message' => 'Unauthenticated'], 401);
                }

                if ($e instanceof AuthorizationException) {
                    return response()->json(['result' => 'error','message' => 'Forbidden'], 403);
                }

                if ($e instanceof HttpExceptionInterface) {
                    return response()->json(['result' => 'error',
                        'message' =>  $e->getMessage()
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
                        'result' => 'error',
                        'message' => 'Database error',
                    ], 500);
                }

                // 3) Verbose fallback in local/testing; generic in prod
                if (app()->environment(['local','testing']) || config('app.debug')) {
                    return response()->json([
                        'result' => 'error',
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
                    'result' => 'error',
                    'message'   => 'Server error',
                    'exception' => config('app.debug') ? class_basename($e) : null,
                ], 500);
            }
        });
    })
    ->create();
