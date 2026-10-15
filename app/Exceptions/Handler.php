<?php

namespace App\Exceptions;

use Throwable;
use Psr\Log\LogLevel;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<Throwable>, LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        ShortenerException::class,
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            if (app()->bound('sentry')) {
                app('sentry')->captureException($e);
            }
        });

        $this->renderable(function (Throwable $e, Request $request) {
            if (($request->is('api/*') && ! $request->is('api/telegram/*')) || $request->is('app/api/*')) {
                return $this->renderApi($e);
            }
        });
    }

    /**
     * One error shape for the API and the mini app: {"error": {"code", "message", "fields"?}}.
     */
    protected function renderApi(Throwable $e): JsonResponse
    {
        [$status, $code, $message, $extra] = match (true) {
            $e instanceof ValidationException     => [422, 'validation', 'The request is invalid.', ['fields' => $e->errors()]],
            $e instanceof ShortenerException      => [422, 'invalid_link', $e->getMessage(), []],
            $e instanceof AuthenticationException => [401, 'unauthenticated', 'A valid API key is required.', []],
            $e instanceof AuthorizationException,
            $e instanceof AccessDeniedHttpException => [403, 'forbidden', $e->getMessage() ?: 'This key does not have the required scope.', []],
            $e instanceof ModelNotFoundException,
            $e instanceof NotFoundHttpException                                 => [404, 'not_found', 'Not found.', []],
            $e instanceof ThrottleRequestsException                             => [429, 'rate_limited', 'Too many requests.', []],
            $e instanceof HttpExceptionInterface && $e->getStatusCode() === 403 => [403, 'forbidden', $e->getMessage() ?: 'Forbidden.', []],
            $e instanceof HttpExceptionInterface                                => [$e->getStatusCode(), 'http_'.$e->getStatusCode(), $e->getMessage() ?: 'Request failed.', []],
            default                                                             => [500, 'server_error', config('app.debug') ? $e->getMessage() : 'Something went wrong.', []],
        };

        $headers = $e instanceof HttpExceptionInterface ? $e->getHeaders() : [];

        return response()->json(['error' => ['code' => $code, 'message' => $message] + $extra], $status, $headers);
    }
}
