<?php

use App\Http\Middleware\AssignRequestId;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\VerifyGestorCatracaWebhookBearer;
use App\Http\Middleware\VerifyHmacSignature;
use App\Http\Middleware\VerifyIntegrationBearerToken;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withCommands([
        __DIR__.'/../app/Console/Commands',
    ])
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
            'verify.catraca.webhook.bearer' => VerifyGestorCatracaWebhookBearer::class,
            'admin' => EnsureAdmin::class,
        ]);
        $middleware->api(prepend: [
            AssignRequestId::class,
        ]);
        $middleware->web(append: [
            EnsureUserIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if ($request->expectsJson()) {
                return null;
            }
            if (! $request->routeIs('users.*')) {
                return null;
            }

            $msg = $e->getMessage();
            if ($msg === '' || $msg === 'This action is unauthorized.') {
                $msg = 'Operação não permitida.';
            }

            return redirect()->back()->withErrors(['user' => $msg]);
        });
    })->create();
