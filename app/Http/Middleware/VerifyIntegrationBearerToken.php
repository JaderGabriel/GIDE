<?php

namespace App\Http\Middleware;

use App\Models\Integration;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyIntegrationBearerToken
{
    public function handle(Request $request, Closure $next, string $integrationKey): Response
    {
        $integration = Integration::query()->where('key', $integrationKey)->first();

        if (! $integration || ! $integration->enabled) {
            return response()->json(['message' => 'Integração desabilitada.'], 403);
        }

        $header = (string) $request->header('Authorization', '');
        $prefix = 'Bearer ';
        $token = str_starts_with($header, $prefix) ? substr($header, strlen($prefix)) : '';
        $token = trim($token);

        $expected = (string) ($integration->auth_token ?? '');
        if ($expected === '' || $token === '' || ! hash_equals($expected, $token)) {
            return response()->json(['message' => 'Token inválido.'], 401);
        }

        $request->attributes->set('integration_key', $integrationKey);

        return $next($request);
    }
}
