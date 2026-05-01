<?php

use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\VerifyHmacSignature;
use App\Http\Middleware\VerifyIntegrationBearerToken;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withEvents()
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'verify.hmac' => VerifyHmacSignature::class,
            'verify.bearer' => VerifyIntegrationBearerToken::class,
            'admin' => EnsureAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
