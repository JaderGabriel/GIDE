<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\FacialEnrollAttempt;
use App\Models\FacialIeducarStatusSnapshot;
use App\Models\FacialSendRequest;
use App\Models\Integration;
use App\Services\Ieducar\IeducarClient;
use App\Services\UserAuditLogger;
use App\Support\AdminListPerPage;
use Illuminate\Http\Request;

class FacialAdminController extends Controller
{
    public function index(Request $request)
    {
        $perPage = AdminListPerPage::resolve($request);

        $items = FacialSendRequest::query()
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        $ids = $items->getCollection()->pluck('id');

        $attemptsByRequest = FacialEnrollAttempt::query()
            ->whereIn('facial_send_request_id', $ids)
            ->orderByDesc('id')
            ->get()
            ->groupBy('facial_send_request_id');

        $statusByRequest = FacialIeducarStatusSnapshot::query()
            ->whereIn('facial_send_request_id', $ids)
            ->orderByDesc('id')
            ->get()
            ->groupBy('facial_send_request_id');

        $ieducar = Integration::query()->where('key', 'ieducar')->first();
        $gestor = Integration::query()->where('key', 'gestor')->first();

        return view('admin.facial_requests', [
            'items' => $items,
            'perPage' => $perPage,
            'attemptsByRequest' => $attemptsByRequest,
            'statusByRequest' => $statusByRequest,
            'hasIeducar' => (bool) $ieducar,
            'hasGestor' => (bool) $gestor,
            'ieducarReady' => (bool) ($ieducar && $ieducar->base_url && ($ieducar->auth_token || data_get($ieducar->extra, 'catraca_frequencia.confirmacao_token'))),
            'stats' => $this->facialAdminGlobalStats(),
        ]);
    }

    /**
     * @return array{total: int, tokens_usados: int, tokens_pendentes: int, catraca_ok: int, catraca_falha: int, catraca_sem_registro: int, com_snapshot_ieducar: int}
     */
    private function facialAdminGlobalStats(): array
    {
        $total = (int) FacialSendRequest::query()->count();
        $tokens_usados = (int) FacialSendRequest::query()->whereNotNull('used_at')->count();
        $tokens_pendentes = max(0, $total - $tokens_usados);

        $maxIds = FacialEnrollAttempt::query()
            ->selectRaw('MAX(id) as max_id')
            ->groupBy('facial_send_request_id')
            ->pluck('max_id')
            ->all();
        $latest = $maxIds === []
            ? collect()
            : FacialEnrollAttempt::query()->whereIn('id', $maxIds)->get()->keyBy('facial_send_request_id');
        $withAttempt = $latest->count();
        $catraca_ok = (int) $latest->filter(fn (FacialEnrollAttempt $a) => $a->ok === true)->count();
        $catraca_falha = (int) $latest->filter(fn (FacialEnrollAttempt $a) => $a->ok === false)->count();
        $catraca_sem_registro = max(0, $total - $withAttempt);

        $snapTable = (new FacialIeducarStatusSnapshot)->getTable();
        $reqTable = (new FacialSendRequest)->getTable();
        $com_snapshot_ieducar = (int) FacialSendRequest::query()->whereExists(function ($q) use ($snapTable, $reqTable) {
            $q->selectRaw('1')
                ->from($snapTable)
                ->whereColumn($snapTable.'.facial_send_request_id', $reqTable.'.id');
        })->count();

        return [
            'total' => $total,
            'tokens_usados' => $tokens_usados,
            'tokens_pendentes' => $tokens_pendentes,
            'catraca_ok' => $catraca_ok,
            'catraca_falha' => $catraca_falha,
            'catraca_sem_registro' => $catraca_sem_registro,
            'com_snapshot_ieducar' => $com_snapshot_ieducar,
        ];
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

            UserAuditLogger::recordAuthenticated('admin.facial.status_refreshed', [
                'ok' => false,
                'reason' => 'ieducar_missing',
            ], 'facial_send_request', $record->id);

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

            UserAuditLogger::recordAuthenticated('admin.facial.status_refreshed', [
                'ok' => false,
                'reason' => 'missing_aluno_identifiers',
            ], 'facial_send_request', $record->id);

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

            UserAuditLogger::recordAuthenticated('admin.facial.status_refreshed', [
                'ok' => true,
                'http_status' => $resp->status(),
                'ieducar_http_success' => $resp->successful(),
            ], 'facial_send_request', $record->id);
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

            UserAuditLogger::recordAuthenticated('admin.facial.status_refreshed', [
                'ok' => false,
                'http_status' => null,
                'error' => $e->getMessage(),
            ], 'facial_send_request', $record->id);
        }

        return back()->with('status', 'Status atualizado.');
    }
}
