<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\FacialEnrollAttempt;
use App\Models\FacialIeducarStatusSnapshot;
use App\Models\FacialSendRequest;
use App\Models\Integration;
use App\Services\Ieducar\IeducarClient;
use Illuminate\Http\Request;

class FacialAdminController extends Controller
{
    public function index(Request $request)
    {
        $items = FacialSendRequest::query()
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        $attemptsByRequest = FacialEnrollAttempt::query()
            ->whereIn('facial_send_request_id', $items->pluck('id'))
            ->orderByDesc('id')
            ->get()
            ->groupBy('facial_send_request_id');

        $statusByRequest = FacialIeducarStatusSnapshot::query()
            ->whereIn('facial_send_request_id', $items->pluck('id'))
            ->orderByDesc('id')
            ->get()
            ->groupBy('facial_send_request_id');

        $ieducar = Integration::query()->where('key', 'ieducar')->first();
        $gestor = Integration::query()->where('key', 'gestor')->first();

        $stats = [
            'total' => $items->count(),
            'tokens_usados' => $items->whereNotNull('used_at')->count(),
            'tokens_pendentes' => $items->whereNull('used_at')->count(),
            'catraca_ok' => 0,
            'catraca_falha' => 0,
            'catraca_sem_registro' => 0,
            'com_snapshot_ieducar' => 0,
        ];
        foreach ($items as $req) {
            $atts = $attemptsByRequest->get($req->id, collect());
            $first = $atts->first();
            if (! $first) {
                $stats['catraca_sem_registro']++;
            } elseif ($first->ok) {
                $stats['catraca_ok']++;
            } else {
                $stats['catraca_falha']++;
            }
            if ($statusByRequest->get($req->id, collect())->isNotEmpty()) {
                $stats['com_snapshot_ieducar']++;
            }
        }

        return view('admin.facial_requests', [
            'items' => $items,
            'attemptsByRequest' => $attemptsByRequest,
            'statusByRequest' => $statusByRequest,
            'hasIeducar' => (bool) $ieducar,
            'hasGestor' => (bool) $gestor,
            'ieducarReady' => (bool) ($ieducar && $ieducar->base_url && ($ieducar->auth_token || data_get($ieducar->extra, 'catraca_frequencia.confirmacao_token'))),
            'stats' => $stats,
        ]);
    }

    public function show(Request $request, int $id)
    {
        $item = FacialSendRequest::query()->findOrFail($id);

        $attempts = FacialEnrollAttempt::query()
            ->where('facial_send_request_id', $item->id)
            ->orderByDesc('id')
            ->get();

        $snapshots = FacialIeducarStatusSnapshot::query()
            ->where('facial_send_request_id', $item->id)
            ->orderByDesc('id')
            ->get();

        return view('admin.facial_request_show', [
            'item' => $item,
            'attempts' => $attempts,
            'snapshots' => $snapshots,
        ]);
    }

    public function refreshStatus(Request $request, int $id)
    {
        $record = FacialSendRequest::query()->findOrFail($id);
        $payload = is_array($record->payload) ? $record->payload : [];

        $codAluno = (string) (data_get($payload, 'aluno_id') ?? '');
        $idpes = (string) (data_get($payload, 'idpes') ?? '');

        $ieducar = Integration::query()->where('key', 'ieducar')->first();
        if (! $ieducar) {
            FacialIeducarStatusSnapshot::query()->create([
                'facial_send_request_id' => $record->id,
                'cod_aluno' => $codAluno !== '' ? $codAluno : null,
                'idpes' => $idpes !== '' ? $idpes : null,
                'http_status' => null,
                'response_json' => null,
                'fetched_at' => now(),
                'error_message' => 'Integração iEducar (key=ieducar) não configurada.',
            ]);

            return back()->with('status', 'Status não atualizado: iEducar não configurado.');
        }

        if ($codAluno === '' && $idpes === '') {
            FacialIeducarStatusSnapshot::query()->create([
                'facial_send_request_id' => $record->id,
                'cod_aluno' => null,
                'idpes' => null,
                'http_status' => null,
                'response_json' => null,
                'fetched_at' => now(),
                'error_message' => 'Payload não contém aluno_id/idpes (não integrado).',
            ]);

            return back()->with('status', 'Status não atualizado: aluno_id/idpes ausentes.');
        }

        try {
            $resp = (new IeducarClient($ieducar))->postCatracaFrequenciaAlunoConsulta([
                'identificacao' => [
                    'cod_aluno' => $codAluno !== '' ? $codAluno : null,
                    'idpes' => $idpes !== '' ? $idpes : null,
                ],
            ]);

            $json = $resp->json();

            FacialIeducarStatusSnapshot::query()->create([
                'facial_send_request_id' => $record->id,
                'cod_aluno' => $codAluno !== '' ? $codAluno : null,
                'idpes' => $idpes !== '' ? $idpes : null,
                'http_status' => $resp->status(),
                'response_json' => $json,
                'fetched_at' => now(),
                'error_message' => $resp->successful() ? null : 'HTTP '.$resp->status(),
            ]);

            // Além da auditoria, atualiza o payload empacotado do request
            // para a tela `/facial/enviar` conseguir usar os dados completos.
            if ($resp->successful() && is_array($json)) {
                $p = is_array($record->payload) ? $record->payload : [];
                $p['ieducar_status'] = $json;
                $record->payload = $p;
                $record->save();
            }
        } catch (\Throwable $e) {
            FacialIeducarStatusSnapshot::query()->create([
                'facial_send_request_id' => $record->id,
                'cod_aluno' => $codAluno !== '' ? $codAluno : null,
                'idpes' => $idpes !== '' ? $idpes : null,
                'http_status' => null,
                'response_json' => null,
                'fetched_at' => now(),
                'error_message' => $e->getMessage(),
            ]);
        }

        return back()->with('status', 'Status atualizado.');
    }
}
