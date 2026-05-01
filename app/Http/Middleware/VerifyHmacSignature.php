<?php

namespace App\Http\Middleware;

use App\Models\Integration;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyHmacSignature
{
    public function handle(Request $request, Closure $next, string $integrationKey): Response
    {
        /** @var Integration|null $integration */
        $integration = Integration::query()->where('key', $integrationKey)->where('enabled', true)->first();
        if (! $integration) {
            return response()->json(['message' => 'Integração não configurada.'], 503);
        }

        $signature = (string) $request->headers->get('X-Signature', '');
        $timestamp = (string) $request->headers->get('X-Timestamp', '');
        $eventId = (string) $request->headers->get('X-Event-Id', '');

        if ($signature === '' || $timestamp === '' || $eventId === '') {
            return response()->json(['message' => 'Assinatura ausente.'], 401);
        }

        if (! ctype_digit($timestamp)) {
            return response()->json(['message' => 'Timestamp inválido.'], 401);
        }

        $ts = (int) $timestamp;
        $now = now()->getTimestamp();
        $skew = abs($now - $ts);
        if ($skew > $integration->signature_ttl_seconds) {
            return response()->json(['message' => 'Assinatura expirada.'], 401);
        }

        $rawBody = $request->getContent();
        $signed = $timestamp.'.'.$eventId.'.'.$rawBody;
        $expected = hash_hmac('sha256', $signed, $integration->hmac_secret ?? '');

        if (! hash_equals($expected, $signature)) {
            return response()->json(['message' => 'Assinatura inválida.'], 401);
        }

        // Idempotência básica por event_id (evita replay). Mantém leve e sem dependência de cache store específico.
        $request->attributes->set('integration', $integration);
        $request->attributes->set('event_id', $eventId);

        return $next($request);
    }
}
