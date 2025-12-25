<?php

use App\Jobs\DeleteExpiredPostsJob;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->renderable(function (Throwable $e, Request $request) {
            if (!$request->expectsJson() && !$request->is('api/*')) {
                return null;
            }

            return match (true) {
                $e instanceof NotFoundHttpException   => error_response('Model not found', 404),
                $e instanceof RouteNotFoundException  => error_response('Route not found', 404),
                $e instanceof AuthenticationException => error_response($e->getMessage(), 401),
                $e instanceof ValidationException     => error_response($e->getMessage(), 422, $e->errors()),
                $e instanceof UnauthorizedException   => error_response($e->getMessage(), 403),
                $e instanceof \Exception             => error_response($e->getMessage(), 400),
                // All other exceptions as server errors (500)
                default                               => error_response($e->getMessage(), 500),
            };
        });
    })->create();
