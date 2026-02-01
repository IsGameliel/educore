<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Mailer\Exception\TransportException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the inputs that are never flashed to the session on validation errors.
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

    public function render($request, Throwable $e)
    {
        // Catch mail errors not handled by renderable()
        if ($e instanceof \Symfony\Component\Mailer\Exception\TransportException) {
            \Log::error('Global mail transport error: '.$e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Email sending failed. Please try again later.',
                ], 500);
            }

            return response()->view('errors.mail', [
                'message' => 'We couldn’t send your email. Please try again later.',
            ], 500);
        }

        // Fallback to parent or your web/api handlers
        return parent::render($request, $e);
    }

    public function register(): void
    {
        $this->renderable(function (TransportException $e, $request) {
            \Log::error('Mail transport failed: '.$e->getMessage());

            // Handle JSON requests (API)
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Email sending failed. Please try again later.',
                ], 500);
            }

            // Handle Web requests
            return response()->view('errors.mail', [
                'message' => 'We couldn’t send your email. Please try again later.',
            ], 500);
        });


        $this->renderable(function (Throwable $e, Request $request) {
            // Handle API requests
            if ($request->expectsJson()) {
                return $this->handleApiException($e, $request);
            }

            // Handle web requests
            return $this->handleWebException($e, $request);
        });
    }

    /**
     * Handle API exceptions with JSON responses.
     */
    protected function handleApiException(Throwable $e, Request $request)
    {
        if ($e instanceof ValidationException) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }

        if ($e instanceof HttpException) {
            return response()->json([
                'error' => $e->getMessage() ?: 'An error occurred',
            ], $e->getStatusCode());
        }

        \Log::error('Unexpected error: ', ['exception' => $e]);

        return response()->json([
            'error' => 'An unexpected error occurred',
        ], 500);
    }

    /**
     * Handle web exceptions with error pages or redirects.
     */
    protected function handleWebException(Throwable $e, Request $request)
    {
        if ($e instanceof ValidationException) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        }

        if ($e instanceof HttpException) {
            $statusCode = $e->getStatusCode();
            if (view()->exists("errors.{$statusCode}")) {
                return response()->view("errors.{$statusCode}", [
                    'exception' => $e,
                    'statusCode' => $statusCode,
                ], $statusCode);
            }
        }

        if (view()->exists('errors.error')) {
            return response()->view('errors.error', [
                'exception' => $e,
                'statusCode' => 500,
            ], 500);
        }

        return parent::prepareResponse($request, $e);
    }

}
