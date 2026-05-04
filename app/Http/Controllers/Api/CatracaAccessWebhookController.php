<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Presence\GestorAccessEventWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Catraca → GIDE: POST /api/v1/catraca/access-events com token em App\Support\GestorCatracaAccessToken
 * (cabeçalho Authorization: Bearer) e corpo JSON do equipamento.
 *
 * Ingestão e auditoria: GestorAccessEventWebhookService (gestor_access_event_deliveries).
 */
class CatracaAccessWebhookController extends Controller
{
    public function store(Request $request, GestorAccessEventWebhookService $service): JsonResponse
    {
        $body = $request->json()->all();
        if (! is_array($body)) {
            return response()->json(['ok' => false, 'message' => 'JSON inválido.'], 400);
        }

        $eventId = (string) ($body['eventId'] ?? $body['event_id'] ?? '');
        if ($eventId === '') {
            return response()->json(['ok' => false, 'message' => 'Campo eventId é obrigatório.'], 400);
        }

        $result = $service->ingestCatracaBearer($eventId, $body);

        $payload = [
            'ok' => true,
            'created' => $result['created'],
            'processed' => $result['processed'],
            'delivery_id' => $result['delivery_id'],
            'eventId' => $eventId,
        ];
        if (! empty($result['queued'])) {
            $payload['queued'] = true;
        }

        return response()->json($payload);
    }
}
