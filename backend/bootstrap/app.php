<?php

use App\Http\Middleware\TrustProxies;
use Illuminate\Foundation\Application;
use App\Http\Middleware\AddRefreshTokenToForm;
use App\Http\Middleware\AddBearerTokenFromCookies;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // $middleware->append(TrustProxies::class);
        $middleware->append(AddRefreshTokenToForm::class);
        $middleware->append(AddBearerTokenFromCookies::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
