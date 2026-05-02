<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendPresenceSms;
use App\Models\AccessEvent;
use App\Models\Integration;
use App\Services\Presence\PresenceMarker;
use App\Services\Presence\PresenceRuleEngine;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Webhook JSON da catraca (Bearer dedicado), alternativa ao HMAC em /api/v1/gestor/access-events.
 */
class CatracaAccessWebhookController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $body = $request->json()->all();
        if (! is_array($body)) {
            return response()->json(['ok' => false, 'message' => 'JSON inválido.'], 400);
        }

        $eventId = (string) ($body['eventId'] ?? $body['event_id'] ?? '');
        if ($eventId === '') {
            return response()->json(['ok' => false, 'message' => 'Campo eventId é obrigatório.'], 400);
        }

        $occurredAt = null;
        $candidateTs = $body['creationDate'] ?? $body['creation_date'] ?? $body['occurred_at'] ?? $body['timestamp'] ?? null;
        if (is_string($candidateTs) && $candidateTs !== '') {
            try {
                $occurredAt = Carbon::parse($candidateTs);
            } catch (\Throwable) {
                $occurredAt = null;
            }
        }

        // Normaliza para motor de presença (mapeamento padrão quando iEducar não define payload_map).
        $normalized = array_merge($body, [
            'aluno_id' => $body['aluno_id'] ?? $body['name'] ?? null,
            'matricula_id' => $body['matricula_id'] ?? $body['matriculaId'] ?? null,
            'type' => $body['type'] ?? $body['way'] ?? $body['accessMedia'] ?? null,
        ]);

        $record = AccessEvent::query()->firstOrCreate(
            ['source' => 'catraca_bearer', 'event_id' => $eventId],
            ['payload' => $normalized, 'occurred_at' => $occurredAt],
        );

        if ($record->wasRecentlyCreated) {
            $ieducar = Integration::query()->where('key', 'ieducar')->where('enabled', true)->first();
            if ($ieducar) {
                $analysis = (new PresenceRuleEngine)->analyze($normalized, $occurredAt, $ieducar);
                $analysis['marker'] = (new PresenceMarker)->mark($ieducar, $analysis);

                $record->analysis = $analysis;
                $record->processed_at = now();
                $record->save();

                if (($analysis['action'] ?? null) === 'mark_presence') {
                    $smsEnabled = Integration::query()->where('key', 'sms')->where('enabled', true)->exists();
                    if ($smsEnabled) {
                        SendPresenceSms::dispatch($eventId, $normalized, $analysis, $occurredAt?->toIso8601String());
                    }
                }
            }
        }

        return response()->json([
            'ok' => true,
            'created' => $record->wasRecentlyCreated,
            'processed' => (bool) ($record->processed_at !== null),
            'eventId' => $eventId,
        ]);
    }
}
