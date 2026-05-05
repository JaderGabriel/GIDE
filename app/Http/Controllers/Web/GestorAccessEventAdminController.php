<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessGestorAccessEventDeliveryJob;
use App\Models\GestorAccessEventDelivery;
use App\Models\Integration;
use App\Services\Presence\PresenceRuleEngine;
use App\Support\AdminListPerPage;
use App\Support\Ieducar\IeducarFrequenciaPreviewMode;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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

        return view('admin.gestor_access_event_show', [
            'delivery' => $delivery,
            'ieducar' => $ieducar,
            'ieducarEnabled' => (bool) ($ieducar?->enabled),
        ]);
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

        if ($delivery->processing_status !== GestorAccessEventDelivery::STATUS_PENDING) {
            $delivery->update([
                'processing_status' => GestorAccessEventDelivery::STATUS_PENDING,
                'processed_at' => null,
            ]);
        }

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
            return redirect()
                ->back()
                ->withErrors(['retry' => 'Falha ao processar agora: '.$e->getMessage()]);
        }

        return redirect()
            ->back()
            ->with('status', 'Processamento forçado executado (sync).');
    }

    public function forceMarkPresence(int $id): RedirectResponse
    {
        $delivery = GestorAccessEventDelivery::query()->with('accessEvent')->findOrFail($id);

        $ieducar = Integration::query()->where('key', 'ieducar')->where('enabled', true)->first();
        if (! $ieducar) {
            return redirect()
                ->back()
                ->withErrors(['retry' => 'Integração iEducar inexistente ou com enabled=false; não é possível enviar.']);
        }

        $payload = is_array($delivery->inbound_payload) ? $delivery->inbound_payload : [];
        $payload = $this->normalizePayloadForPresence($delivery->inbound_channel ?? null, $payload);
        data_set($payload, 'action.mark_presence', true);

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
        $analysis['reason'] = ($analysis['reason'] ?? '').' (override admin: mark_presence=true)';

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

        ProcessGestorAccessEventDeliveryJob::dispatch($delivery->id);

        return redirect()
            ->back()
            ->with('status', 'Override aplicado: mark_presence=true. Envio ao iEducar reenfileirado.');
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
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
}
