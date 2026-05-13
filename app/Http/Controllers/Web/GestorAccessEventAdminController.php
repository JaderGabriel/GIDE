<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessGestorAccessEventDeliveryJob;
use App\Models\GestorAccessEventDelivery;
use App\Models\Integration;
use App\Models\SmsDelivery;
use App\Models\SmsTemplate;
use App\Services\Presence\PresenceRuleEngine;
use App\Services\Sms\SmsService;
use App\Support\AdminListPerPage;
use App\Support\Ieducar\IeducarFrequenciaPreviewMode;
use App\Support\Presence\AccessEventOccurredAtResolver;
use App\Support\SmsTemplateKey;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class GestorAccessEventAdminController extends Controller
{
    public function index(Request $request)
    {
        $perPage = AdminListPerPage::resolve($request);

        $q = GestorAccessEventDelivery::query()->orderByDesc('id');

        $status = trim((string) $request->query('status', ''));
        if ($status !== '' && in_array($status, [
            GestorAccessEventDelivery::STATUS_PENDING,
            GestorAccessEventDelivery::STATUS_PROCESSING,
            GestorAccessEventDelivery::STATUS_COMPLETED,
            GestorAccessEventDelivery::STATUS_FAILED,
            GestorAccessEventDelivery::STATUS_SKIPPED,
        ], true)) {
            $q->where('processing_status', $status);
        }

        $items = $q->paginate($perPage)->withQueryString();

        $ieducarEnabled = Integration::query()->where('key', 'ieducar')->where('enabled', true)->exists();

        return view('admin.gestor_access_events', [
            'items' => $items,
            'perPage' => $perPage,
            'statusFilter' => $status,
            'ieducarEnabled' => $ieducarEnabled,
        ]);
    }

    public function show(int $id)
    {
        $delivery = GestorAccessEventDelivery::query()->with('accessEvent')->findOrFail($id);
        $ieducar = Integration::query()->where('key', 'ieducar')->first();
        $sms = Integration::query()->where('key', 'sms')->first();

        $payload = is_array($delivery->inbound_payload) ? $delivery->inbound_payload : [];
        $payload = $this->normalizePayloadForPresence($delivery->inbound_channel ?? null, $payload);
        $guardianDigits = SmsService::extractGuardianRecipientDigitsFromPayload($payload);
        $guardianMasked = array_map(fn (string $d) => $this->maskE164DigitsForDisplay($d), $guardianDigits);

        $smsDeliveries = SmsDelivery::query()
            ->where('event_id', (string) $delivery->event_id)
            ->orderByDesc('updated_at')
            ->get();

        $smsSendTimeline = $this->flattenSmsSendLogsForAdmin($smsDeliveries);

        return view('admin.gestor_access_event_show', [
            'delivery' => $delivery,
            'ieducar' => $ieducar,
            'ieducarEnabled' => (bool) ($ieducar?->enabled),
            'smsIntegrationEnabled' => (bool) ($sms?->enabled),
            'smsTemplateCatracaEnabled' => SmsTemplate::query()->where('key', SmsTemplateKey::PRESENCE_CATRACA)->where('enabled', true)->exists(),
            'smsTemplateIeducarEnabled' => SmsTemplate::query()->where('key', SmsTemplateKey::PRESENCE_IEDUCAR_SYNC)->where('enabled', true)->exists(),
            'smsGuardianMasked' => $guardianMasked,
            'smsDeliveries' => $smsDeliveries,
            'smsSendTimeline' => $smsSendTimeline,
        ]);
    }

    public function resendPresenceSmsConfig(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'template' => ['required', Rule::in([SmsTemplateKey::PRESENCE_CATRACA, SmsTemplateKey::PRESENCE_IEDUCAR_SYNC])],
        ]);

        $delivery = GestorAccessEventDelivery::query()->findOrFail($id);

        if (! Integration::query()->where('key', 'sms')->where('enabled', true)->exists()) {
            return redirect()
                ->back()
                ->withErrors(['sms' => 'Integração SMS inexistente ou desligada.']);
        }

        [$payload, $analysis, $occurredAt, $ieducarHttpLabel] = $this->buildSmsResendContext($delivery);
        $templateKey = (string) $data['template'];
        $extra = $templateKey === SmsTemplateKey::PRESENCE_IEDUCAR_SYNC
            ? ['ieducar_http_status' => $ieducarHttpLabel]
            : [];

        try {
            app(SmsService::class)->sendPresenceSms(
                (string) $delivery->event_id,
                $payload,
                $analysis,
                $occurredAt,
                $templateKey,
                $extra,
                true,
                null,
                'admin_resend_config',
            );
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->withErrors(['sms' => $e->getMessage()]);
        }

        $this->appendReprocessingLog($delivery, 'sms_resend_config', [
            'template' => $templateKey,
        ]);

        $templateLabel = $templateKey === SmsTemplateKey::PRESENCE_IEDUCAR_SYNC
            ? 'Confirmação no iEducar'
            : 'Presença na catraca';

        return redirect()
            ->back()
            ->with('sms_success', 'SMS enviado com sucesso. Modo: conforme a integração em /integracoes/sms. Template: '.$templateLabel.'. Envio imediato (sem fila).');
    }

    public function resendPresenceSmsGuardians(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'template' => ['required', Rule::in([SmsTemplateKey::PRESENCE_CATRACA, SmsTemplateKey::PRESENCE_IEDUCAR_SYNC])],
        ]);

        $delivery = GestorAccessEventDelivery::query()->findOrFail($id);

        if (! Integration::query()->where('key', 'sms')->where('enabled', true)->exists()) {
            return redirect()
                ->back()
                ->withErrors(['sms' => 'Integração SMS inexistente ou desligada.']);
        }

        [$payload, $analysis, $occurredAt, $ieducarHttpLabel] = $this->buildSmsResendContext($delivery);
        $phones = SmsService::extractGuardianRecipientDigitsFromPayload($payload);
        if ($phones === []) {
            return redirect()
                ->back()
                ->withErrors(['sms' => 'Nenhum telefone de responsável encontrado no payload (chaves como phone, responsavel.phone ou lista responsaveis).']);
        }

        $templateKey = (string) $data['template'];
        $extra = $templateKey === SmsTemplateKey::PRESENCE_IEDUCAR_SYNC
            ? ['ieducar_http_status' => $ieducarHttpLabel]
            : [];

        try {
            app(SmsService::class)->sendPresenceSms(
                (string) $delivery->event_id,
                $payload,
                $analysis,
                $occurredAt,
                $templateKey,
                $extra,
                true,
                $phones,
                'admin_resend_guardians',
            );
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->withErrors(['sms' => $e->getMessage()]);
        }

        $this->appendReprocessingLog($delivery, 'sms_resend_guardians', [
            'template' => $templateKey,
            'recipients_count' => count($phones),
        ]);

        $templateLabel = $templateKey === SmsTemplateKey::PRESENCE_IEDUCAR_SYNC
            ? 'Confirmação no iEducar'
            : 'Presença na catraca';

        return redirect()
            ->back()
            ->with('sms_success', 'SMS enviado com sucesso para '.count($phones).' número(es) de responsável(is). Template: '.$templateLabel.'. Envio imediato (sem fila).');
    }

    public function retry(int $id): RedirectResponse
    {
        $delivery = GestorAccessEventDelivery::query()->findOrFail($id);

        if (! in_array($delivery->processing_status, [
            GestorAccessEventDelivery::STATUS_PENDING,
            GestorAccessEventDelivery::STATUS_FAILED,
            GestorAccessEventDelivery::STATUS_PROCESSING,
        ], true)) {
            return redirect()
                ->back()
                ->withErrors(['retry' => 'Só é possível reenfileirar entregas pendentes, em processamento ou com falha no iEducar.']);
        }

        $previousStatus = $delivery->processing_status;

        if ($delivery->processing_status !== GestorAccessEventDelivery::STATUS_PENDING) {
            $delivery->update([
                'processing_status' => GestorAccessEventDelivery::STATUS_PENDING,
                'processed_at' => null,
            ]);
        }

        $this->appendReprocessingLog($delivery, 'retry', [
            'previous_status' => $previousStatus,
        ]);

        ProcessGestorAccessEventDeliveryJob::dispatch($delivery->id);

        return redirect()
            ->back()
            ->with('status', 'Reenvio ao iEducar enfileirado.');
    }

    public function requeue(int $id): RedirectResponse
    {
        $delivery = GestorAccessEventDelivery::query()->findOrFail($id);

        if ($delivery->processing_status !== GestorAccessEventDelivery::STATUS_PENDING) {
            return redirect()
                ->back()
                ->withErrors(['retry' => 'Só é possível reenfileirar quando a entrega está pendente.']);
        }

        $this->appendReprocessingLog($delivery, 'requeue', []);

        ProcessGestorAccessEventDeliveryJob::dispatch($delivery->id);

        return redirect()
            ->back()
            ->with('status', 'Entrega pendente reenfileirada.');
    }

    public function forceProcess(int $id): RedirectResponse
    {
        $delivery = GestorAccessEventDelivery::query()->findOrFail($id);

        if ($delivery->processing_status !== GestorAccessEventDelivery::STATUS_PENDING) {
            return redirect()
                ->back()
                ->withErrors(['retry' => 'Só é possível forçar processamento quando a entrega está pendente.']);
        }

        try {
            ProcessGestorAccessEventDeliveryJob::dispatchSync($delivery->id);
        } catch (\Throwable $e) {
            $this->appendReprocessingLog($delivery, 'force_process', [
                'result' => 'error',
                'error' => mb_substr($e->getMessage(), 0, 500),
            ]);

            return redirect()
                ->back()
                ->withErrors(['retry' => 'Falha ao processar agora: '.$e->getMessage()]);
        }

        $delivery->refresh();
        $this->appendReprocessingLog($delivery, 'force_process', [
            'result' => 'ok',
            'new_status' => $delivery->processing_status,
        ]);

        return redirect()
            ->back()
            ->with('status', 'Processamento forçado executado (sync).');
    }

    /**
     * Reavalia o evento pelo motor de presença (sem forçar mark_presence=true).
     * Só enfileira envio ao iEducar se a reavaliação resultar em action=mark_presence.
     */
    public function forceMarkPresence(int $id): RedirectResponse
    {
        $delivery = GestorAccessEventDelivery::query()->with('accessEvent')->findOrFail($id);

        $ieducar = Integration::query()->where('key', 'ieducar')->where('enabled', true)->first();
        if (! $ieducar) {
            return redirect()
                ->back()
                ->withErrors(['retry' => 'Integração iEducar inexistente ou com enabled=false; não é possível enviar.']);
        }

        $previousAnalysis = $delivery->analysis_json ?? [];
        $previousStatus = $delivery->processing_status;

        $payload = is_array($delivery->inbound_payload) ? $delivery->inbound_payload : [];
        $payload = $this->normalizePayloadForPresence($delivery->inbound_channel ?? null, $payload);

        $occurredAt = $this->resolveOccurredAtFromPayload($payload, $delivery->inbound_channel ?? null);
        $analysis = (new PresenceRuleEngine)->analyze($payload, $occurredAt, $ieducar);

        $gestorEnv = strtolower(trim((string) ($delivery->gestor_ie_environment ?? 'homolog')));
        if ($gestorEnv !== 'preview' && $gestorEnv !== 'homolog') {
            $gestorEnv = 'homolog';
        }
        $metaPreview = IeducarFrequenciaPreviewMode::resolveMetaPreview($gestorEnv, false, false);

        $analysis['gestor_ieducar_environment'] = $gestorEnv;
        $analysis['ieducar_outbound_channel'] = 'catraca_frequencia_registro';
        $analysis['ieducar_outbound_preview_only'] = $metaPreview;
        $analysis['inbound_channel'] = (string) ($delivery->inbound_channel ?? '');
        $analysis['reason'] = ($analysis['reason'] ?? '').' (reavaliação admin)';

        $willSend = ($analysis['action'] ?? '') === 'mark_presence';

        $logExtra = [
            'previous_action' => $previousAnalysis['action'] ?? null,
            'previous_status' => $previousStatus,
            'new_action' => $analysis['action'] ?? null,
            'new_reason' => $analysis['reason'] ?? null,
            'will_send_ieducar' => $willSend,
        ];

        if ($willSend) {
            $delivery->update([
                'analysis_json' => $analysis,
                'processing_status' => GestorAccessEventDelivery::STATUS_PENDING,
                'processed_at' => null,
                'ieducar_marker_summary' => null,
                'ieducar_frequencia_request_json' => null,
                'ieducar_frequencia_http_status' => null,
                'ieducar_frequencia_response_json' => null,
                'ieducar_frequencia_error' => null,
                'ieducar_preview_only' => $metaPreview,
            ]);

            if ($delivery->accessEvent) {
                $delivery->accessEvent->analysis = $analysis;
                $delivery->accessEvent->processed_at = null;
                $delivery->accessEvent->save();
            }

            $this->appendReprocessingLog($delivery, 'reevaluate_presence', $logExtra);

            ProcessGestorAccessEventDeliveryJob::dispatch($delivery->id);

            return redirect()
                ->back()
                ->with('status', 'Motor reavaliou: action=mark_presence. Envio ao iEducar enfileirado.');
        }

        $delivery->update(['analysis_json' => $analysis]);

        $this->appendReprocessingLog($delivery, 'reevaluate_presence', $logExtra);

        return redirect()
            ->back()
            ->withErrors(['retry' => 'Motor reavaliou e decidiu: action='
                .($analysis['action'] ?? '?')
                .' — '
                .($analysis['reason'] ?? '')
                .'. Sem envio ao iEducar.']);
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, mixed>, 2: ?Carbon, 3: string}
     */
    private function buildSmsResendContext(GestorAccessEventDelivery $delivery): array
    {
        $payload = is_array($delivery->inbound_payload) ? $delivery->inbound_payload : [];
        $payload = $this->normalizePayloadForPresence($delivery->inbound_channel ?? null, $payload);
        $analysis = is_array($delivery->analysis_json) ? $delivery->analysis_json : [];
        $occurredAt = $this->resolveOccurredAtFromPayload($payload, $delivery->inbound_channel ?? null);
        $http = $delivery->ieducar_frequencia_http_status;
        $ieducarHttpLabel = $http !== null ? (string) $http : '—';

        return [$payload, $analysis, $occurredAt, $ieducarHttpLabel];
    }

    /**
     * @param  Collection<int, SmsDelivery>  $rows
     * @return list<array<string, mixed>>
     */
    private function flattenSmsSendLogsForAdmin(Collection $rows): array
    {
        $flat = [];
        foreach ($rows as $row) {
            $meta = $this->smsDeliveryAdminMeta($row);
            $ctx = is_array($row->context) ? $row->context : [];
            $logs = is_array($ctx['send_log'] ?? null) ? array_values($ctx['send_log']) : [];

            if ($logs === []) {
                $at = $row->sent_at ?? $row->updated_at;
                $atIso = $at !== null ? $at->toIso8601String() : '';
                $atDisplay = $at !== null
                    ? $at->timezone(config('app.timezone'))->format('d/m/Y H:i:s')
                    : '—';
                $toDigits = (string) $row->to;
                $toMask = strlen($toDigits) <= 4
                    ? str_repeat('•', strlen($toDigits))
                    : str_repeat('•', strlen($toDigits) - 4).substr($toDigits, -4);
                $e = [
                    '_synthetic' => true,
                    '_sms_delivery_id' => $row->id,
                    '_delivery_meta' => $meta,
                    'at' => $atIso,
                    'at_display' => $atDisplay,
                    'trigger' => 'registry_snapshot',
                    'template_key' => (string) $row->template_key,
                    'to_masked' => $toMask,
                    'status' => (string) $row->status,
                    'message_preview' => mb_substr((string) $row->message, 0, 220),
                    'http_status' => $row->last_http_status,
                    'provider_message_id' => $row->provider_message_id !== null && (string) $row->provider_message_id !== ''
                        ? (string) $row->provider_message_id
                        : null,
                    'error_snippet' => $row->last_error ? mb_substr((string) $row->last_error, 0, 200) : null,
                ];
                $e['_ui'] = $this->smsTimelineUiHints($e, $meta);
                $flat[] = $e;

                continue;
            }

            foreach ($logs as $e) {
                if (! is_array($e)) {
                    continue;
                }
                $e['_synthetic'] = false;
                $e['_sms_delivery_id'] = $row->id;
                $e['_delivery_meta'] = $meta;
                $atRaw = (string) ($e['at'] ?? '');
                try {
                    $e['at_display'] = Carbon::parse($atRaw)->timezone(config('app.timezone'))->format('d/m/Y H:i:s');
                } catch (\Throwable) {
                    $e['at_display'] = $atRaw !== '' ? $atRaw : '—';
                }
                $e['_ui'] = $this->smsTimelineUiHints($e, $meta);
                $flat[] = $e;
            }
        }

        usort($flat, fn (array $a, array $b): int => strcmp((string) ($b['at'] ?? ''), (string) ($a['at'] ?? '')));

        return $flat;
    }

    /**
     * @return array<string, mixed>
     */
    private function smsDeliveryAdminMeta(SmsDelivery $row): array
    {
        $pr = is_array($row->provider_response) ? $row->provider_response : null;
        $apiStatus = null;
        if ($pr !== null) {
            foreach (['status', 'messageStatus', 'MessageStatus'] as $k) {
                $v = $pr[$k] ?? null;
                if (is_string($v) && $v !== '') {
                    $apiStatus = strtolower(trim($v));

                    break;
                }
            }
        }

        return [
            'provider' => (string) $row->provider,
            'provider_message_id' => (string) ($row->provider_message_id ?? ''),
            'message' => (string) $row->message,
            'last_error' => $row->last_error !== null ? (string) $row->last_error : '',
            'row_status' => (string) $row->status,
            'sent_at_display' => $row->sent_at?->timezone(config('app.timezone'))->format('d/m/Y H:i:s'),
            'provider_api_status' => $apiStatus,
            'http_status' => $row->last_http_status,
        ];
    }

    /**
     * @param  array<string, mixed>  $e  linha do send_log ou sintética
     * @param  array<string, mixed>  $meta  {@see smsDeliveryAdminMeta()}
     * @return array{api: string, api_label: string, delivery: string, delivery_label: string}
     */
    private function smsTimelineUiHints(array $e, array $meta): array
    {
        $logSt = (string) ($e['status'] ?? '');
        $http = $e['http_status'] ?? $meta['http_status'] ?? null;
        $httpOk = $http === null || ((int) $http >= 200 && (int) $http < 300);
        $pApi = is_string($meta['provider_api_status'] ?? null) ? (string) $meta['provider_api_status'] : '';

        if ($logSt === 'error') {
            return [
                'api' => 'danger',
                'api_label' => 'Falha junto ao provedor'.($http !== null ? ' (HTTP '.$http.')' : ''),
                'delivery' => 'na',
                'delivery_label' => '—',
            ];
        }

        if ($logSt === 'sent' && ! $httpOk) {
            return [
                'api' => 'warn',
                'api_label' => 'Marcado como enviado no registo, mas o último HTTP conhecido não é 2xx'.($http !== null ? ' ('.$http.')' : ''),
                'delivery' => 'neutral',
                'delivery_label' => 'Ver detalhes do provedor ou reenvio.',
            ];
        }

        if ($logSt === 'sent' && $httpOk) {
            $deliveredStates = ['delivered'];
            $failedStates = ['undelivered', 'failed', 'canceled', 'cancelled', 'rejected'];
            $inTransitStates = ['queued', 'accepted', 'scheduled', 'sending', 'sent', 'received', 'read'];

            if ($pApi !== '' && in_array($pApi, $deliveredStates, true)) {
                return [
                    'api' => 'success',
                    'api_label' => 'Aceite na API do provedor',
                    'delivery' => 'success',
                    'delivery_label' => 'Entregue (estado reportado pelo provedor: '.$pApi.')',
                ];
            }

            if ($pApi !== '' && in_array($pApi, $failedStates, true)) {
                return [
                    'api' => 'success',
                    'api_label' => 'HTTP OK; mensagem criada',
                    'delivery' => 'danger',
                    'delivery_label' => 'Estado no provedor: '.$pApi,
                ];
            }

            if ($pApi !== '' && in_array($pApi, $inTransitStates, true)) {
                return [
                    'api' => 'success',
                    'api_label' => 'Aceite na API do provedor',
                    'delivery' => 'warn',
                    'delivery_label' => 'Em trânsito / sem confirmação de entrega ao telemóvel (estado: '.$pApi.')',
                ];
            }

            return [
                'api' => 'success',
                'api_label' => 'Aceite na API do provedor',
                'delivery' => 'neutral',
                'delivery_label' => $pApi === ''
                    ? 'Entrega ao telemóvel: não veio na resposta HTTP de criação (webhooks de entrega não estão integrados).'
                    : 'Estado no provedor: '.$pApi,
            ];
        }

        if (in_array($logSt, ['pending', ''], true) || ($logSt !== 'sent' && $logSt !== 'error')) {
            return [
                'api' => 'neutral',
                'api_label' => $logSt === 'pending' || $logSt === '' ? 'Registo ainda não concluído junto ao provedor' : 'Estado: '.$logSt,
                'delivery' => 'na',
                'delivery_label' => '—',
            ];
        }

        return [
            'api' => 'neutral',
            'api_label' => 'Estado: '.$logSt,
            'delivery' => 'na',
            'delivery_label' => '—',
        ];
    }

    private function maskE164DigitsForDisplay(string $digits): string
    {
        $len = strlen($digits);
        if ($len <= 4) {
            return str_repeat('•', $len);
        }

        return str_repeat('•', $len - 4).substr($digits, -4);
    }

    private function normalizePayloadForPresence(?string $inboundChannel, array $payload): array
    {
        if ($inboundChannel !== GestorAccessEventDelivery::CHANNEL_CATRACA_BEARER) {
            return $payload;
        }

        return array_merge($payload, [
            'aluno_id' => $payload['aluno_id'] ?? $payload['name'] ?? null,
            'matricula_id' => $payload['matricula_id'] ?? $payload['matriculaId'] ?? null,
            'type' => $payload['type'] ?? $payload['way'] ?? $payload['accessMedia'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveOccurredAtFromPayload(array $payload, ?string $inboundChannel = null): ?Carbon
    {
        return AccessEventOccurredAtResolver::resolve($payload, $inboundChannel)['occurred_at'];
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function appendReprocessingLog(GestorAccessEventDelivery $delivery, string $action, array $extra): void
    {
        $log = is_array($delivery->reprocessing_log) ? $delivery->reprocessing_log : [];

        $log[] = array_filter([
            'action' => $action,
            'at' => now()->toIso8601String(),
            'user' => Auth::user()?->name ?? Auth::user()?->email ?? 'system',
        ] + $extra, fn ($v) => $v !== null);

        $delivery->update(['reprocessing_log' => $log]);
    }
}
