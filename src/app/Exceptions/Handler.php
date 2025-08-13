<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;


class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types with their custom log levels.
     */
    protected $levels = [
        // Custom log levels
    ];

    /**
     * A list of the exception types that are not reported.
     */
    protected $dontReport = [
        // Exceptions not reported
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register any exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            // Custom reporting logic
        });
    }

    /**
     * Customize the response for unauthenticated requests.
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        return response()->json(['message' => 'Unauthenticated.'], 401);

    }
    
    public function invalidJson($request, ValidationException $exception): JsonResponse
    {
        return response()->json([
            'message' => 'Validation failed.',
            'errors' => $exception->errors(),
        ], 422);
    }

    public function render($request, Throwable $e)
    {
        // Return JSON for any /api/* route OR when the client wants/expects JSON
        if ($request->is('api/*') || $request->wantsJson() || $request->expectsJson()) {

            if ($e instanceof \Illuminate\Validation\ValidationException) {
                return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
            }

            if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                return response()->json(['message' => 'Resource not found'], 404);
            }

            if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }

            if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                return response()->json(['message' => 'Forbidden'], 403);
            }

            return response()->json([
                'message'    => $e->getMessage() ?: 'Server error',
                'exception'  => class_basename($e),
                'trace'      => config('app.debug') ? collect($e->getTrace())->take(5) : [],
            ], 500);
        }

        return parent::render($request, $e);
    }


}
