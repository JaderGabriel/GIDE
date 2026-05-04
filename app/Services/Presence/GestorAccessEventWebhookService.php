<?php

namespace App\Services\Presence;

use App\Jobs\SendPresenceSms;
use App\Models\AccessEvent;
use App\Models\GestorAccessEventDelivery;
use App\Models\Integration;
use App\Services\Ieducar\IeducarClient;
use App\Support\Ieducar\GideFrequenciaRegistroPlanB;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Ingestão de eventos de acesso: {@see GestorWebhookController} (HMAC) e {@see CatracaAccessWebhookController} (Bearer).
 * Persiste `access_events` + `gestor_access_event_deliveries`; iEducar apenas em preview (catraca-frequência).
 */
class GestorAccessEventWebhookService
{
    /**
     * @return array{created: bool, processed: bool, delivery_id: int}
     */
    public function handle(Request $request, string $eventId): array
    {
        $payload = $request->json()->all();
        if (! is_array($payload)) {
            $payload = [];
        }

        return $this->ingest(
            $eventId,
            'gestor',
            GestorAccessEventDelivery::CHANNEL_GESTOR_HMAC,
            $payload,
            $payload,
        );
    }

    /**
     * @param  array<string, mixed>  $rawDeviceJson  JSON bruto da catraca (auditoria e access_events.payload).
     * @return array{created: bool, processed: bool, delivery_id: int}
     */
    public function ingestCatracaBearer(string $eventId, array $rawDeviceJson): array
    {
        return $this->ingest(
            $eventId,
            'catraca_bearer',
            GestorAccessEventDelivery::CHANNEL_CATRACA_BEARER,
            $rawDeviceJson,
            $this->normalizeCatracaDevicePayload($rawDeviceJson),
        );
    }

    /**
     * @param  array<string, mixed>  $inboundPayloadForAudit
     * @param  array<string, mixed>  $payloadForPresence
     * @return array{created: bool, processed: bool, delivery_id: int}
     */
    private function ingest(
        string $eventId,
        string $accessEventSource,
        string $inboundChannel,
        array $inboundPayloadForAudit,
        array $payloadForPresence,
    ): array {
        $occurredAt = $this->resolveOccurredAtFromPayload($payloadForPresence);

        $gestor = Integration::query()->where('key', 'gestor')->first();
        $gestorEnv = strtolower(trim((string) data_get($gestor?->extra, 'ieducar_processing.environment', 'homolog')));
        if ($gestorEnv !== 'preview' && $gestorEnv !== 'homolog') {
            $gestorEnv = 'homolog';
        }

        $delivery = GestorAccessEventDelivery::query()->create([
            'event_id' => $eventId,
            'inbound_channel' => $inboundChannel,
            'inbound_payload' => $inboundPayloadForAudit,
            'processing_status' => GestorAccessEventDelivery::STATUS_PENDING,
            'gestor_ie_environment' => $gestorEnv,
            'ieducar_preview_only' => true,
        ]);

        $record = AccessEvent::query()->firstOrCreate(
            ['source' => $accessEventSource, 'event_id' => $eventId],
            ['payload' => $inboundPayloadForAudit, 'occurred_at' => $occurredAt],
        );

        $delivery->update([
            'access_event_id' => $record->id,
            'access_event_was_created' => $record->wasRecentlyCreated,
        ]);

        $ieducar = Integration::query()->where('key', 'ieducar')->where('enabled', true)->first();

        if (! $ieducar) {
            $delivery->update([
                'processing_status' => GestorAccessEventDelivery::STATUS_SKIPPED,
                'ieducar_frequencia_error' => 'Integração iEducar desabilitada ou inexistente.',
                'processed_at' => now(),
            ]);

            return [
                'created' => $record->wasRecentlyCreated,
                'processed' => false,
                'delivery_id' => $delivery->id,
            ];
        }

        $analysis = (new PresenceRuleEngine)->analyze($payloadForPresence, $occurredAt, $ieducar);
        $analysis['gestor_ieducar_environment'] = $gestorEnv;
        $analysis['ieducar_outbound_channel'] = 'catraca_frequencia_registro';
        $analysis['ieducar_outbound_preview_only'] = true;
        $analysis['inbound_channel'] = $inboundChannel;

        $previewOutcome = $this->runIeducarFrequenciaPreviewOnly($ieducar, $analysis, $occurredAt);

        $analysis['marker'] = $previewOutcome['marker'];

        $delivery->update([
            'analysis_json' => $analysis,
            'ieducar_marker_summary' => $previewOutcome['marker'],
            'ieducar_frequencia_request_json' => $previewOutcome['request_json'],
            'ieducar_frequencia_http_status' => $previewOutcome['http_status'],
            'ieducar_frequencia_response_json' => $previewOutcome['response_json'],
            'ieducar_frequencia_error' => $previewOutcome['error'],
            'processing_status' => $previewOutcome['delivery_status'],
            'processed_at' => now(),
        ]);

        if ($record->wasRecentlyCreated) {
            $record->analysis = $analysis;
            $record->processed_at = now();
            $record->save();

            if (($analysis['action'] ?? null) === 'mark_presence') {
                $smsEnabled = Integration::query()->where('key', 'sms')->where('enabled', true)->exists();
                if ($smsEnabled) {
                    SendPresenceSms::dispatch($eventId, $payloadForPresence, $analysis, $occurredAt?->toIso8601String());
                }
            }
        }

        return [
            'created' => $record->wasRecentlyCreated,
            'processed' => true,
            'delivery_id' => $delivery->id,
        ];
    }

    private function resolveOccurredAtFromPayload(array $payload): ?Carbon
    {
        $candidateTs = data_get($payload, 'occurred_at')
            ?? data_get($payload, 'timestamp')
            ?? data_get($payload, 'event_time')
            ?? data_get($payload, 'creationDate')
            ?? data_get($payload, 'creation_date');

        if (! is_string($candidateTs) || $candidateTs === '') {
            return null;
        }

        try {
            return Carbon::parse($candidateTs);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function normalizeCatracaDevicePayload(array $body): array
    {
        return array_merge($body, [
            'aluno_id' => $body['aluno_id'] ?? $body['name'] ?? null,
            'matricula_id' => $body['matricula_id'] ?? $body['matriculaId'] ?? null,
            'type' => $body['type'] ?? $body['way'] ?? $body['accessMedia'] ?? null,
        ]);
    }

    /**
     * @return array{
     *   marker: array<string, mixed>,
     *   request_json: ?array,
     *   http_status: ?int,
     *   response_json: ?array,
     *   error: ?string,
     *   delivery_status: string
     * }
     */
    private function runIeducarFrequenciaPreviewOnly(Integration $ieducar, array $analysis, ?Carbon $occurredAt): array
    {
        $markerBase = [
            'channel' => 'catraca_frequencia_registro',
            'meta_preview' => true,
        ];

        if (($analysis['action'] ?? null) !== 'mark_presence') {
            return [
                'marker' => array_merge($markerBase, [
                    'status' => 'skipped',
                    'reason' => 'Motor de presença não marcou envio (action≠mark_presence).',
                    'analysis_action' => $analysis['action'] ?? null,
                ]),
                'request_json' => null,
                'http_status' => null,
                'response_json' => null,
                'error' => null,
                'delivery_status' => GestorAccessEventDelivery::STATUS_SKIPPED,
            ];
        }

        $rawAluno = data_get($analysis, 'aluno_id');
        $codAluno = is_numeric($rawAluno) ? (int) $rawAluno : (int) preg_replace('/\D/', '', (string) $rawAluno);
        if ($codAluno < 1) {
            return [
                'marker' => array_merge($markerBase, [
                    'status' => 'skipped',
                    'reason' => 'cod_aluno ausente ou inválido para o contrato Plan B de frequência.',
                ]),
                'request_json' => null,
                'http_status' => null,
                'response_json' => null,
                'error' => null,
                'delivery_status' => GestorAccessEventDelivery::STATUS_SKIPPED,
            ];
        }

        $day = $occurredAt?->copy()->timezone(config('app.timezone', 'UTC')) ?? now(config('app.timezone', 'UTC'));
        $dataRef = $day->format('Y-m-d');

        $row = [
            'meta' => [
                'contract_version' => IeducarClient::CAT_FREQUENCIA_CONTRACT_VERSION,
            ],
            'fonte' => 'gide',
            'presente' => true,
            'identificacao' => [
                'cod_aluno' => $codAluno,
            ],
            'data_ref' => $dataRef,
        ];

        try {
            $normalized = GideFrequenciaRegistroPlanB::validateAndNormalize($row);
        } catch (Throwable $e) {
            Log::warning('gestor_access_event.frequencia_preview_validate_failed', [
                'message' => $e->getMessage(),
            ]);

            return [
                'marker' => array_merge($markerBase, [
                    'status' => 'error',
                    'reason' => 'Validação Plan B: '.$e->getMessage(),
                ]),
                'request_json' => $row,
                'http_status' => null,
                'response_json' => null,
                'error' => $e->getMessage(),
                'delivery_status' => GestorAccessEventDelivery::STATUS_FAILED,
            ];
        }

        $normalized = GideFrequenciaRegistroPlanB::refreshDataRefsWithRandomClock($normalized);
        $meta = (array) ($normalized['meta'] ?? []);
        $meta['contract_version'] = IeducarClient::CAT_FREQUENCIA_CONTRACT_VERSION;
        $meta['preview'] = true;
        $normalized['meta'] = $meta;

        try {
            $client = new IeducarClient($ieducar);
            $resp = $client->postCatracaFrequenciaRegistro($normalized);
            $json = $resp->json();

            return [
                'marker' => array_merge($markerBase, [
                    'status' => $resp->successful() ? 'ok' : 'error',
                    'http_status' => $resp->status(),
                ]),
                'request_json' => $normalized,
                'http_status' => $resp->status(),
                'response_json' => is_array($json) ? $json : ['raw' => $resp->body()],
                'error' => $resp->successful() ? null : mb_substr((string) $resp->body(), 0, 8000),
                'delivery_status' => $resp->successful()
                    ? GestorAccessEventDelivery::STATUS_COMPLETED
                    : GestorAccessEventDelivery::STATUS_FAILED,
            ];
        } catch (Throwable $e) {
            Log::warning('gestor_access_event.frequencia_preview_http_failed', [
                'message' => $e->getMessage(),
            ]);

            return [
                'marker' => array_merge($markerBase, [
                    'status' => 'error',
                    'reason' => $e->getMessage(),
                ]),
                'request_json' => $normalized,
                'http_status' => null,
                'response_json' => null,
                'error' => $e->getMessage(),
                'delivery_status' => GestorAccessEventDelivery::STATUS_FAILED,
            ];
        }
    }
}
