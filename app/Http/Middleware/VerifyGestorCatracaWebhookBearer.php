<?php

namespace App\Http\Middleware;

use App\Models\Integration;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

/**
 * Valida Authorization: Bearer contra hash guardado em integrations.extra (Gestor).
 * O valor em texto plano só é mostrado uma vez na UI ao gerar o token.
 */
class VerifyGestorCatracaWebhookBearer
{
    public function handle(Request $request, Closure $next): Response
    {
        $integration = Integration::query()->where('key', 'gestor')->first();

        if (! $integration || ! $integration->enabled) {
            return response()->json(['message' => 'Integração Gestor desabilitada ou inexistente.'], 403);
        }

        $hash = (string) data_get($integration->extra, 'catraca_webhook_bearer_hash', '');
        if ($hash === '') {
            return response()->json(['message' => 'Token do webhook da catraca não configurado. Gere um token em Integrações → Gestor.'], 503);
        }

        $header = (string) $request->header('Authorization', '');
        $prefix = 'Bearer ';
        $token = str_starts_with($header, $prefix) ? trim(substr($header, strlen($prefix))) : '';
        if ($token === '') {
            return response()->json(['message' => 'Cabeçalho Authorization Bearer ausente.'], 401);
        }

        if (! Hash::check($token, $hash)) {
            return response()->json(['message' => 'Token inválido.'], 401);
        }

        $request->attributes->set('gestor_integration', $integration);

        return $next($request);
    }
}
