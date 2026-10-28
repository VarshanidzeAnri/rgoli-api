<?php

use App\Http\Middleware\AddRequestContext;
use App\Http\Middleware\SetLocale;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api/v1',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // SetLocale runs first so even auth/throttle errors are translated.
        $middleware->api(prepend: [AddRequestContext::class, SetLocale::class]);
        $middleware->throttleApi('api');
        $middleware->alias([
            'permission' => PermissionMiddleware::class,
            'role' => RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(fn (AuthenticationException $e, Request $request) => response()->json(['message' => __('messages.unauthenticated')], 401));

        $exceptions->render(fn (AuthorizationException|AccessDeniedHttpException|UnauthorizedException $e, Request $request) => response()->json(['message' => __('messages.forbidden')], 403));

        $exceptions->render(fn (ModelNotFoundException|NotFoundHttpException $e, Request $request) => response()->json(['message' => __('messages.not_found')], 404));

        $exceptions->render(fn (ThrottleRequestsException $e, Request $request) => response()->json(['message' => __('messages.too_many_requests')], 429, $e->getHeaders()));
    })->create();
