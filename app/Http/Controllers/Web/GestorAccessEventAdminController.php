<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessGestorAccessEventDeliveryJob;
use App\Models\GestorAccessEventDelivery;
use App\Models\Integration;
use App\Models\SmsTemplate;
use App\Services\Presence\PresenceRuleEngine;
use App\Services\Sms\SmsService;
use App\Support\AdminListPerPage;
use App\Support\Ieducar\IeducarFrequenciaPreviewMode;
use App\Support\SmsTemplateKey;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        return view('admin.gestor_access_event_show', [
            'delivery' => $delivery,
            'ieducar' => $ieducar,
            'ieducarEnabled' => (bool) ($ieducar?->enabled),
            'smsIntegrationEnabled' => (bool) ($sms?->enabled),
            'smsTemplateCatracaEnabled' => SmsTemplate::query()->where('key', SmsTemplateKey::PRESENCE_CATRACA)->where('enabled', true)->exists(),
            'smsTemplateIeducarEnabled' => SmsTemplate::query()->where('key', SmsTemplateKey::PRESENCE_IEDUCAR_SYNC)->where('enabled', true)->exists(),
            'smsGuardianMasked' => $guardianMasked,
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
            );
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->withErrors(['sms' => $e->getMessage()]);
        }

        $this->appendReprocessingLog($delivery, 'sms_resend_config', [
            'template' => $templateKey,
        ]);

        return redirect()
            ->back()
            ->with('status', 'SMS reenviado conforme a configuração atual da integração (template '.$templateKey.').');
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

        return redirect()
            ->back()
            ->with('status', 'SMS enviado para '.count($phones).' número(es) de responsável(is) encontrado(s) no payload.');
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

        $occurredAt = $this->resolveOccurredAtFromPayload($payload);
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
        $occurredAt = $this->resolveOccurredAtFromPayload($payload);
        $http = $delivery->ieducar_frequencia_http_status;
        $ieducarHttpLabel = $http !== null ? (string) $http : '—';

        return [$payload, $analysis, $occurredAt, $ieducarHttpLabel];
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
        } catch (\Throwable) {
            return null;
        }
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
