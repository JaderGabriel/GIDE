<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendPresenceSms;
use App\Models\AccessEvent;
use App\Models\Integration;
use App\Services\Presence\PresenceMarker;
use App\Services\Presence\PresenceRuleEngine;
use Carbon\Carbon;
use Illuminate\Http\Request;

class GestorWebhookController extends Controller
{
    public function store(Request $request)
    {
        $eventId = (string) $request->attributes->get('event_id', '');
        if ($eventId === '') {
            return response()->json(['message' => 'Event id ausente.'], 400);
        }

        // Payload é específico do Gestor; por ora aceitamos qualquer JSON e persistimos bruto.
        $payload = $request->json()->all();

        $occurredAt = null;
        $candidateTs = data_get($payload, 'occurred_at') ?? data_get($payload, 'timestamp') ?? data_get($payload, 'event_time');
        if (is_string($candidateTs) && $candidateTs !== '') {
            try {
                $occurredAt = Carbon::parse($candidateTs);
            } catch (\Throwable) {
                $occurredAt = null;
            }
        }

        $record = AccessEvent::query()->firstOrCreate(
            ['source' => 'gestor', 'event_id' => $eventId],
            ['payload' => $payload, 'occurred_at' => $occurredAt],
        );

        // Processamento MVP: analisar e (quando configurado) lançar presença.
        if ($record->wasRecentlyCreated) {
            $ieducar = Integration::query()->where('key', 'ieducar')->where('enabled', true)->first();
            if ($ieducar) {
                $gestor = Integration::query()->where('key', 'gestor')->first();

                $analysis = (new PresenceRuleEngine)->analyze($payload, $occurredAt, $ieducar);
                if ($gestor) {
                    $analysis['gestor_ieducar_environment'] = (string) data_get($gestor->extra, 'ieducar_processing.environment', 'homolog');
                }
                $analysis['marker'] = (new PresenceMarker)->mark($ieducar, $analysis);

                $record->analysis = $analysis;
                $record->processed_at = now();
                $record->save();

                // SMS: dispara após apontamento de presença (evento dentro da janela).
                // Requer integração SMS habilitada e template ativo.
                if (($analysis['action'] ?? null) === 'mark_presence') {
                    $smsEnabled = Integration::query()->where('key', 'sms')->where('enabled', true)->exists();
                    if ($smsEnabled) {
                        SendPresenceSms::dispatch($eventId, $payload, $analysis, $occurredAt?->toIso8601String());
                    }
                }
            }
        }

        return response()->json([
            'ok' => true,
            'created' => $record->wasRecentlyCreated,
            'processed' => (bool) ($record->processed_at !== null),
        ]);
    }
}
