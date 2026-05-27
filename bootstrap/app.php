<?php

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\ViteManifestNotFoundException;
use Illuminate\Http\Request;
use Symfony\Component\Mailer\Exception\TransportException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'prevent.retake' => \App\Http\Middleware\PreventTestRetake::class,
            'usertype' => \App\Http\Middleware\UsertypeMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $isDatabaseConnectionException = static function (\Throwable $exception): bool {
            if ($exception instanceof QueryException) {
                $message = strtolower($exception->getMessage());

                return str_contains($message, 'sqlstate[hy000] [2002]')
                    || str_contains($message, 'connection refused')
                    || str_contains($message, 'actively refused')
                    || str_contains($message, 'server has gone away')
                    || str_contains($message, 'no such file or directory');
            }

            if ($exception instanceof \PDOException) {
                $message = strtolower($exception->getMessage());

                return str_contains($message, 'sqlstate[hy000] [2002]')
                    || str_contains($message, 'connection refused')
                    || str_contains($message, 'actively refused')
                    || str_contains($message, 'server has gone away')
                    || str_contains($message, 'no such file or directory');
            }

            return false;
        };

        $exceptions->render(function (\Throwable $exception, Request $request) use ($isDatabaseConnectionException) {
            if ($exception instanceof TransportException) {
                \Log::error('Mail transport error while handling request.', [
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                    'exception' => $exception,
                ]);

                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'We could not send email right now. Please try again in a few minutes.',
                    ], 503);
                }

                return response()->view('errors.mail', [
                    'title' => 'Email Service Unavailable',
                    'message' => 'We could not send your email right now because the mail service is temporarily unavailable.',
                    'hint' => 'Please try again in a few minutes. If the problem keeps happening, check your mail configuration or network connection.',
                ], 503);
            }

            if ($exception instanceof ViteManifestNotFoundException) {
                \Log::warning('Vite manifest is missing while handling request.', [
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                    'exception' => $exception,
                ]);

                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Frontend assets are not available right now. Run the Vite build and try again.',
                    ], 503);
                }

                return response()->view('errors.assets-unavailable', [
                    'title' => 'Frontend Assets Unavailable',
                    'message' => 'The application UI assets have not been built yet, so this page cannot load correctly right now.',
                    'hint' => 'Run "npm run build" for production assets or "npm run dev" while developing, then refresh the page.',
                ], 503);
            }

            if (! $isDatabaseConnectionException($exception)) {
                return null;
            }

            \Log::error('Database connection error while handling request.', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'exception' => $exception,
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'The application is temporarily unable to connect to the database. Please try again in a moment.',
                ], 503);
            }

            return response()->view('errors.database-unavailable', [
                'title' => 'Service Temporarily Unavailable',
                'message' => 'We are having trouble connecting to the database right now. Please try again in a moment.',
            ], 503);
        });
    })->create();
