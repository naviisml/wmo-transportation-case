<?php

use App\Exceptions\BookingConflict;
use App\Exceptions\InvalidBooking;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->report(function (QueryException $exception) {
            // SQL bindings can contain addresses or tokens.
            Log::error('Database operation failed.', [
                'exception' => $exception::class,
                'sqlstate' => $exception->errorInfo[0] ?? null,
            ]);
        })->stop();
        $exceptions->render(function (Throwable $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }
            if ($exception instanceof BookingConflict) {
                return response()->json(['message' => $exception->getMessage()], 409);
            }
            if ($exception instanceof InvalidBooking) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => [$exception->field => [$exception->getMessage()]],
                ], 422);
            }
            if ($exception instanceof HttpExceptionInterface) {
                $status = $exception->getStatusCode();
                if ($status === 409) {
                    return response()->json(['message' => $exception->getMessage()], 409);
                }
                if ($status >= 500) {
                    return response()->json(['message' => 'The API is temporarily unavailable.'], $status);
                }

                return response()->json(['message' => Response::$statusTexts[$status] ?? 'Request failed.'], $status, $exception->getHeaders());
            }
            if (! $exception instanceof ValidationException && ! $exception instanceof AuthenticationException && ! $exception instanceof AuthorizationException && ! $exception instanceof ModelNotFoundException && ! $exception instanceof HttpExceptionInterface) {
                return response()->json(['message' => 'The API is temporarily unavailable.'], 500);
            }

            return null;
        });
        $exceptions->shouldRenderJsonWhen(fn ($request, $e) => $request->is('api/*') || $request->expectsJson());
    })->create();
