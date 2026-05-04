<?php

namespace App\Http\Middleware;

use App\Models\Integration;
use App\Support\GestorCatracaAccessToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Valida Authorization: Bearer contra o hash em GestorCatracaAccessToken (integração Gestor).
 */
class VerifyGestorCatracaWebhookBearer
{
    public function handle(Request $request, Closure $next): Response
    {
        $integration = Integration::query()->where('key', 'gestor')->first();

        if (! $integration || ! $integration->enabled) {
            return response()->json(['message' => 'Integração Gestor desabilitada ou inexistente.'], 403);
        }

        if (! GestorCatracaAccessToken::isConfigured($integration)) {
            return response()->json(['message' => 'Token de acesso da catraca não configurado. Gere em Integrações → Gestor.'], 503);
        }

        $header = (string) $request->header('Authorization', '');
        $prefix = 'Bearer ';
        $token = str_starts_with($header, $prefix) ? trim(substr($header, strlen($prefix))) : '';
        if ($token === '') {
            return response()->json(['message' => 'Cabeçalho Authorization Bearer ausente.'], 401);
        }

        if (! GestorCatracaAccessToken::checkPlainAgainstIntegration($token, $integration)) {
            return response()->json(['message' => 'Token de acesso inválido.'], 401);
        }

        $request->attributes->set('gestor_integration', $integration);

        return $next($request);
    }
}
