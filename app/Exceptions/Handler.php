<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
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
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $e)
    {
        // If the request expects JSON (API), return JSON response
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->prepareJsonResponse($request, $e);
        }

        // For Inertia requests, render error pages
        if (Inertia::isInertiaRequest()) {
            return $this->renderInertiaError($request, $e);
        }

        // Fall back to default Laravel error handling
        return parent::render($request, $e);
    }

    /**
     * Render Inertia error pages.
     */
    protected function renderInertiaError(Request $request, Throwable $e): \Symfony\Component\HttpFoundation\Response
    {
        $exception = $e instanceof HttpException ? $e : null;
        $statusCode = $exception ? $exception->getStatusCode() : 500;
        $exceptionMessage = $exception ? $exception->getMessage() : $e->getMessage();

        // Encode exception for URL passing
        $encodedException = urlencode($e->getMessage() . "\n" . $e->getTraceAsString());

        switch ($statusCode) {
            case 404:
                return Inertia::render('errors/404', [
                    'message' => 'The requested page was not found.',
                    'exception' => $encodedException,
                    'status' => 404,
                ])->toResponse($request)->setStatusCode(404);

            case 419:
                return Inertia::render('errors/419', [
                    'message' => 'Page expired due to session timeout.',
                    'status' => 419,
                ])->toResponse($request)->setStatusCode(419);

            case 403:
                return Inertia::render('errors/403', [
                    'message' => 'You do not have permission to access this page.',
                    'status' => 403,
                ])->toResponse($request)->setStatusCode(403);

            case 500:
            default:
                return Inertia::render('errors/500', [
                    'message' => 'An unexpected error occurred.',
                    'exception' => $encodedException,
                    'status' => 500,
                ])->toResponse($request)->setStatusCode($statusCode);
        }
    }

    /**
     * Prepare a JSON response for the given exception.
     */
    protected function prepareJsonResponse($request, Throwable $e)
    {
        $response = [
            'success' => false,
            'message' => $e->getMessage(),
        ];

        if (config('app.debug')) {
            $response['exception'] = $e->getMessage();
            $response['trace'] = $e->getTraceAsString();
        }

        $statusCode = 500;
        if ($e instanceof HttpException) {
            $statusCode = $e->getStatusCode();
        }

        return response()->json($response, $statusCode);
    }
}
