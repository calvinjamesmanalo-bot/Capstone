<?php

use App\Http\Middleware\EnforceProductionHttps;
use App\Http\Middleware\EnsureStudentAccountIsVerified;
use App\Http\Middleware\RequireRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Run before sessions and CSRF so insecure requests are upgraded before
        // any authentication state or form data is processed.
        $middleware->web(prepend: [
            EnforceProductionHttps::class,
        ]);
        $middleware->web(append: [
            'throttle:web',
        ]);

        $middleware->alias([
            'role' => RequireRole::class,
            'student.verified' => EnsureStudentAccountIsVerified::class,
        ]);

        $middleware->redirectTo(
            guests: '/login',
            users: '/'
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash('cf-turnstile-response');

        $exceptions->respond(function (Response $response) {
            if ($response->getStatusCode() === 419) {
                return redirect('/login?session_expired=1');
            }

            return $response;
        });
    })->create();
