<?php

declare(strict_types=1);

use App\Exceptions\BaseException;
use App\Http\Responses\ApiResponse;
use App\Providers\FoundationServiceProvider;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withProviders([
        FoundationServiceProvider::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        // Render all exceptions as JSON for API requests
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // Laravel validation errors → 422
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error(
                    message: 'Validation failed.',
                    code: 422,
                    errors: $e->errors(),
                );
            }
        });

        // Our custom BaseException hierarchy → use the exception's own HTTP code
        $exceptions->render(function (BaseException $e, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error(
                    message: $e->getMessage(),
                    code: $e->getHttpStatusCode(),
                );
            }
        });

        // Laravel's AuthorizationException → 403
        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error(
                    message: $e->getMessage() ?: 'You do not have permission to perform this action.',
                    code: 403,
                );
            }
        });

        // Eloquent's ModelNotFoundException → 404
        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error(
                    message: 'The requested resource was not found.',
                    code: 404,
                );
            }
        });

        // Symfony's 404 (route not found) → 404
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error(
                    message: 'The requested endpoint does not exist.',
                    code: 404,
                );
            }
        });

    })->create();
