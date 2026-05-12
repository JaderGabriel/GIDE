<?php

namespace App\Services\Presence;

use App\Jobs\ProcessGestorAccessEventDeliveryJob;
use App\Jobs\SendPresenceSms;
use App\Models\AccessEvent;
use App\Models\GestorAccessEventDelivery;
use App\Models\Integration;
use App\Services\Enrichment\StudentEnrichmentService;
use App\Services\Ieducar\IeducarClient;
use App\Support\Ieducar\GideFrequenciaRegistroPlanB;
use App\Support\Ieducar\IeducarFrequenciaPreviewMode;
use App\Support\SmsTemplateKey;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Ingestão de eventos de acesso: {@see GestorWebhookController} (HMAC) e {@see CatracaAccessWebhookController} (Bearer).
 * Persiste `access_events` + `gestor_access_event_deliveries`; iEducar em preview (catraca-frequência) via fila quando há POST real.
 */
class GestorAccessEventWebhookService
{
    /**
     * @return array{created: bool, processed: bool, delivery_id: int, queued?: bool}
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
     * @return array{created: bool, processed: bool, delivery_id: int, queued?: bool}
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
     * Executa o preview HTTP ao iEducar para uma linha de auditoria (job ou comando).
     */
    public function processIeducarForDelivery(int $deliveryId): void
    {
        $claimed = false;
        DB::transaction(function () use ($deliveryId, &$claimed): void {
            $d = GestorAccessEventDelivery::query()->whereKey($deliveryId)->lockForUpdate()->first();
            if (! $d || $d->processing_status !== GestorAccessEventDelivery::STATUS_PENDING) {
                return;
            }
            $d->update(['processing_status' => GestorAccessEventDelivery::STATUS_PROCESSING]);
            $claimed = true;
        });

        if (! $claimed) {
            return;
        }

        $delivery = GestorAccessEventDelivery::query()->find($deliveryId);
        if (! $delivery) {
            return;
        }

        $ieducar = Integration::query()->where('key', 'ieducar')->where('enabled', true)->first();
        $analysis = is_array($delivery->analysis_json) ? $delivery->analysis_json : [];
        $payloadForPresence = is_array($delivery->inbound_payload) ? $delivery->inbound_payload : [];
        $occurredAt = $this->resolveOccurredAtFromPayload($payloadForPresence);
        $eventId = (string) $delivery->event_id;

        if (! $ieducar) {
            $delivery->update([
                'processing_status' => GestorAccessEventDelivery::STATUS_SKIPPED,
                'ieducar_frequencia_error' => 'Integração iEducar inexistente ou com enabled=false no worker.',
                'processed_at' => now(),
                'ieducar_attempts' => (int) $delivery->ieducar_attempts + 1,
            ]);

            return;
        }

        $metaPreview = (bool) ($delivery->ieducar_preview_only ?? true);
        $previewOutcome = $this->runIeducarFrequenciaRegistro($ieducar, $analysis, $occurredAt, $metaPreview);
        $analysis['marker'] = $previewOutcome['marker'];
        $analysis['inbound_channel'] = $analysis['inbound_channel'] ?? $delivery->inbound_channel;

        $delivery->update([
            'analysis_json' => $analysis,
            'ieducar_marker_summary' => $previewOutcome['marker'],
            'ieducar_frequencia_request_json' => $previewOutcome['request_json'],
            'ieducar_frequencia_http_status' => $previewOutcome['http_status'],
            'ieducar_frequencia_response_json' => $previewOutcome['response_json'],
            'ieducar_frequencia_error' => $previewOutcome['error'],
            'processing_status' => $previewOutcome['delivery_status'],
            'processed_at' => now(),
            'ieducar_attempts' => (int) $delivery->ieducar_attempts + 1,
        ]);

        $record = AccessEvent::query()->find($delivery->access_event_id);
        if ($record && $delivery->access_event_was_created) {
            $record->analysis = $analysis;
            $record->processed_at = now();
            $record->save();
        }

        $this->dispatchPresenceIeducarSyncSmsIfApplicable(
            (bool) $delivery->access_event_was_created,
            $eventId,
            $payloadForPresence,
            $analysis,
            $occurredAt,
            (string) $previewOutcome['delivery_status'],
            $previewOutcome['http_status'],
        );
    }

    /**
     * @param  array<string, mixed>  $inboundPayloadForAudit
     * @param  array<string, mixed>  $payloadForPresence
     * @return array{created: bool, processed: bool, delivery_id: int, queued?: bool}
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
        $metaPreview = IeducarFrequenciaPreviewMode::resolveMetaPreview($gestorEnv, false, false);

        $requestId = request()->attributes->get('request_id');

        $delivery = GestorAccessEventDelivery::query()->create([
            'event_id' => $eventId,
            'inbound_channel' => $inboundChannel,
            'inbound_payload' => $inboundPayloadForAudit,
            'processing_status' => GestorAccessEventDelivery::STATUS_PENDING,
            'gestor_ie_environment' => $gestorEnv,
            'ieducar_preview_only' => $metaPreview,
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
                'ieducar_frequencia_error' => 'Integração iEducar inexistente ou com enabled=false no momento deste POST (não houve chamada HTTP).',
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
        $analysis['ieducar_outbound_preview_only'] = $metaPreview;
        $analysis['inbound_channel'] = $inboundChannel;

        $analysis['enrichment'] = $this->tryEnrich($analysis);
        if ($requestId) {
            $analysis['request_id'] = $requestId;
        }

        if ($this->shouldQueueIeducarPreview($analysis)) {
            $delivery->update([
                'analysis_json' => $analysis,
                'processing_status' => GestorAccessEventDelivery::STATUS_PENDING,
                'processed_at' => null,
            ]);
            $record->analysis = $analysis;
            $record->save();

            $this->dispatchPresenceCatracaSmsIfApplicable(
                $record->wasRecentlyCreated,
                $eventId,
                $payloadForPresence,
                $analysis,
                $occurredAt,
            );

            ProcessGestorAccessEventDeliveryJob::dispatch($delivery->id);
            $delivery->refresh();

            return [
                'created' => $record->wasRecentlyCreated,
                'processed' => $delivery->processing_status !== GestorAccessEventDelivery::STATUS_PENDING,
                'delivery_id' => $delivery->id,
                'queued' => true,
            ];
        }

        $previewOutcome = $this->runIeducarFrequenciaRegistro($ieducar, $analysis, $occurredAt, $metaPreview);

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
        }

        $this->dispatchPresenceCatracaSmsIfApplicable(
            $record->wasRecentlyCreated,
            $eventId,
            $payloadForPresence,
            $analysis,
            $occurredAt,
        );

        $this->dispatchPresenceIeducarSyncSmsIfApplicable(
            $record->wasRecentlyCreated,
            $eventId,
            $payloadForPresence,
            $analysis,
            $occurredAt,
            (string) $previewOutcome['delivery_status'],
            $previewOutcome['http_status'],
        );

        return [
            'created' => $record->wasRecentlyCreated,
            'processed' => true,
            'delivery_id' => $delivery->id,
        ];
    }

    private function dispatchPresenceCatracaSmsIfApplicable(
        bool $accessEventWasCreated,
        string $eventId,
        array $payloadForPresence,
        array $analysis,
        ?Carbon $occurredAt,
    ): void {
        if (! $accessEventWasCreated || ($analysis['action'] ?? null) !== 'mark_presence') {
            return;
        }
        if (! Integration::query()->where('key', 'sms')->where('enabled', true)->exists()) {
            return;
        }

        SendPresenceSms::dispatch(
            $eventId,
            $payloadForPresence,
            $analysis,
            $occurredAt?->toIso8601String(),
            SmsTemplateKey::PRESENCE_CATRACA,
        );
    }

    private function dispatchPresenceIeducarSyncSmsIfApplicable(
        bool $accessEventWasCreated,
        string $eventId,
        array $payloadForPresence,
        array $analysis,
        ?Carbon $occurredAt,
        string $ieducarDeliveryStatus,
        ?int $ieducarHttpStatus,
    ): void {
        if (! $accessEventWasCreated || ($analysis['action'] ?? null) !== 'mark_presence') {
            return;
        }
        if ($ieducarDeliveryStatus !== GestorAccessEventDelivery::STATUS_COMPLETED) {
            return;
        }
        if (! Integration::query()->where('key', 'sms')->where('enabled', true)->exists()) {
            return;
        }

        $httpLabel = $ieducarHttpStatus !== null ? (string) $ieducarHttpStatus : 'OK';

        SendPresenceSms::dispatch(
            $eventId,
            $payloadForPresence,
            $analysis,
            $occurredAt?->toIso8601String(),
            SmsTemplateKey::PRESENCE_IEDUCAR_SYNC,
            ['ieducar_http_status' => $httpLabel],
        );
    }

    /**
     * @param  array<string, mixed>  $analysis
     */
    private function shouldQueueIeducarPreview(array $analysis): bool
    {
        if (($analysis['action'] ?? null) !== 'mark_presence') {
            return false;
        }

        $rawAluno = data_get($analysis, 'aluno_id');
        $codAluno = is_numeric($rawAluno) ? (int) $rawAluno : (int) preg_replace('/\D/', '', (string) $rawAluno);

        return $codAluno >= 1;
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
     * @param  array<string, mixed>  $analysis
     * @return array<string, mixed>|null
     */
    private function tryEnrich(array $analysis): ?array
    {
        $rawAluno = data_get($analysis, 'aluno_id');
        $codAluno = is_numeric($rawAluno) ? (int) $rawAluno : (int) preg_replace('/\D/', '', (string) $rawAluno);

        if ($codAluno < 1) {
            return null;
        }

        try {
            return (new StudentEnrichmentService)->enrich($codAluno);
        } catch (Throwable $e) {
            Log::debug('gestor_access_event.enrichment_failed', ['error' => $e->getMessage()]);

            return null;
        }
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
    private function runIeducarFrequenciaRegistro(Integration $ieducar, array $analysis, ?Carbon $occurredAt, bool $metaPreview): array
    {
        $markerBase = [
            'channel' => 'catraca_frequencia_registro',
            'meta_preview' => $metaPreview,
        ];

        if (($analysis['action'] ?? null) !== 'mark_presence') {
            $action = $analysis['action'] ?? null;

            return [
                'marker' => array_merge($markerBase, [
                    'status' => 'skipped',
                    'reason' => 'Motor de presença não marcou envio ao iEducar (é necessário action=mark_presence).',
                    'analysis_action' => $action,
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
                    'reason' => 'cod_aluno ausente ou inválido após mapeamento (ex.: `aluno_id` / `name` no JSON) — não é montado body Plan B para catraca-frequência.',
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
                'preview' => $metaPreview,
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
        $meta['preview'] = $metaPreview;
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
