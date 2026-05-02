<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Jobs\SendIeducarFrequenciaRegistroJob;
use App\Models\IeducarFrequenciaRegistroDelivery;
use App\Models\Integration;
use App\Services\Ieducar\IeducarClient;
use App\Services\UserAuditLogger;
use App\Support\Ieducar\GideFrequenciaRegistroPlanB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class IeducarFrequenciaRegistroController extends Controller
{
    public function index(Request $request): View
    {
        $ieducar = Integration::query()->where('key', 'ieducar')->first();

        $recent = IeducarFrequenciaRegistroDelivery::query()
            ->orderByDesc('id')
            ->limit(40)
            ->get();

        $defaultPayload = [
            'meta' => [
                'contract_version' => IeducarClient::CAT_FREQUENCIA_CONTRACT_VERSION,
                'preview' => true,
            ],
            'fonte' => 'gide',
            'presente' => true,
            'identificacao' => [
                'cod_aluno' => 211,
            ],
            'data_ref' => now()->toIso8601String(),
        ];

        return view('integrations.ieducar_frequencia_registro', [
            'ieducar' => $ieducar,
            'recent' => $recent,
            'defaultPayloadJson' => json_encode($defaultPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'targetPath' => IeducarClient::CAT_FREQUENCIA_REGISTRO_PATH,
        ]);
    }

    public function show(int $id): View
    {
        $delivery = IeducarFrequenciaRegistroDelivery::query()->findOrFail($id);

        return view('integrations.ieducar_frequencia_registro_show', [
            'delivery' => $delivery,
        ]);
    }

    /**
     * Reprocessa imediatamente uma entrega pendente (mesmo job, síncrono).
     */
    public function forceSend(Request $request, int $id): RedirectResponse
    {
        $delivery = IeducarFrequenciaRegistroDelivery::query()->findOrFail($id);

        if ($delivery->status !== IeducarFrequenciaRegistroDelivery::STATUS_PENDING) {
            UserAuditLogger::recordAuthenticated('frequencia.force_send', [
                'ok' => false,
                'delivery_id' => $delivery->id,
                'reason' => 'not_pending',
                'status' => $delivery->status,
            ], 'ieducar_frequencia_delivery', $delivery->id);

            return back()->with('status', 'Só é possível forçar envio quando o status for pendente (atual: '.$delivery->status.').');
        }

        if (! Integration::query()->where('key', 'ieducar')->where('enabled', true)->exists()) {
            UserAuditLogger::recordAuthenticated('frequencia.force_send', [
                'ok' => false,
                'delivery_id' => $delivery->id,
                'reason' => 'ieducar_disabled',
            ], 'ieducar_frequencia_delivery', $delivery->id);

            return back()->with('status', 'Integração iEducar não está habilitada; não foi possível enviar.');
        }

        SendIeducarFrequenciaRegistroJob::dispatchSync($delivery->id);

        UserAuditLogger::recordAuthenticated('frequencia.force_send', [
            'ok' => true,
            'delivery_id' => $delivery->id,
        ], 'ieducar_frequencia_delivery', $delivery->id);

        return back()->with('status', 'Envio forçado concluído para #'.$delivery->id.'. Atualize se ainda vir dados antigos em cache.');
    }

    /**
     * Preview: enfileira envio com meta.preview=true (acompanhar na mesma tela de detalhe).
     */
    public function preview(Request $request): RedirectResponse
    {
        $payload = $this->validatedPayloadFromRequest($request);
        $payload['meta'] = [
            'contract_version' => IeducarClient::CAT_FREQUENCIA_CONTRACT_VERSION,
            'preview' => true,
        ];

        $ieducar = Integration::query()->where('key', 'ieducar')->where('enabled', true)->first();
        if (! $ieducar) {
            return back()->withErrors(['payload' => 'Integração iEducar não encontrada ou desabilitada.'])->withInput();
        }

        $delivery = IeducarFrequenciaRegistroDelivery::query()->create([
            'user_id' => $request->user()?->id,
            'mode' => IeducarFrequenciaRegistroDelivery::MODE_PREVIEW,
            'status' => IeducarFrequenciaRegistroDelivery::STATUS_PENDING,
            'payload' => $payload,
        ]);

        SendIeducarFrequenciaRegistroJob::dispatch($delivery->id);

        UserAuditLogger::recordAuthenticated('frequencia.preview_enqueued', [
            'delivery_id' => $delivery->id,
        ], 'ieducar_frequencia_delivery', $delivery->id);

        return redirect()
            ->route('integrations.ieducar.frequencia-registro.show', ['id' => $delivery->id])
            ->with('status', 'Preview enfileirado (#'.$delivery->id.'). Atualize a página após o worker processar.');
    }

    /**
     * Gravação: enfileira envio com meta.preview=false (o job força preview false).
     */
    public function enqueue(Request $request): RedirectResponse
    {
        $payload = $this->validatedPayloadFromRequest($request);
        $payload['meta'] = [
            'contract_version' => IeducarClient::CAT_FREQUENCIA_CONTRACT_VERSION,
            'preview' => false,
        ];

        $ieducar = Integration::query()->where('key', 'ieducar')->where('enabled', true)->first();
        if (! $ieducar) {
            return back()->withErrors(['payload' => 'Integração iEducar não encontrada ou desabilitada.'])->withInput();
        }

        $delivery = IeducarFrequenciaRegistroDelivery::query()->create([
            'user_id' => $request->user()?->id,
            'mode' => IeducarFrequenciaRegistroDelivery::MODE_APPLY,
            'status' => IeducarFrequenciaRegistroDelivery::STATUS_PENDING,
            'payload' => $payload,
        ]);

        SendIeducarFrequenciaRegistroJob::dispatch($delivery->id);

        UserAuditLogger::recordAuthenticated('frequencia.apply_enqueued', [
            'delivery_id' => $delivery->id,
        ], 'ieducar_frequencia_delivery', $delivery->id);

        return redirect()
            ->route('integrations.ieducar.frequencia-registro.show', ['id' => $delivery->id])
            ->with('status', 'Lote enfileirado. O worker vai enviar ao i-Educar e atualizar o status (#'.$delivery->id.').');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPayloadFromRequest(Request $request): array
    {
        $validated = $request->validate([
            'payload' => ['required', 'string', 'max:512000'],
        ]);

        $decoded = json_decode($validated['payload'], true);
        if (! is_array($decoded)) {
            throw ValidationException::withMessages([
                'payload' => 'JSON inválido (esperado objeto JSON).',
            ]);
        }

        return GideFrequenciaRegistroPlanB::validateAndNormalize($decoded);
    }
}
