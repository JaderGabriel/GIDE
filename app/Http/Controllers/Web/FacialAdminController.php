<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\FacialEnrollAttempt;
use App\Models\FacialGestorCatracaHistory;
use App\Models\FacialIeducarStatusSnapshot;
use App\Models\FacialSendRequest;
use App\Models\GestorGuestLink;
use App\Models\Integration;
use App\Services\Gestor\GestorClient;
use App\Services\Ieducar\IeducarClient;
use App\Services\UserAuditLogger;
use App\Support\AdminListPerPage;
use Illuminate\Http\Request;

class FacialAdminController extends Controller
{
    public function index(Request $request)
    {
        $perPage = AdminListPerPage::resolve($request);

        $filters = [
            'q' => trim((string) $request->query('q', '')), // token/event_id
            'token' => trim((string) $request->query('token', '')),
            'event' => trim((string) $request->query('event', '')),
            'cod_aluno' => trim((string) $request->query('cod_aluno', '')),
            'idpes' => trim((string) $request->query('idpes', '')),
            'token_status' => (string) $request->query('token_status', ''), // used|pending|valid|expired
            'catraca' => (string) $request->query('catraca', ''), // ok|fail|none
        ];

        $q = FacialSendRequest::query();

        if ($filters['q'] !== '') {
            $term = $filters['q'];
            $q->where(function ($qq) use ($term) {
                $qq->where('token', 'like', '%'.$term.'%')
                    ->orWhere('event_id', 'like', '%'.$term.'%');
            });
        }
        if ($filters['token'] !== '') {
            $q->where('token', 'like', '%'.$filters['token'].'%');
        }
        if ($filters['event'] !== '') {
            $q->where('event_id', 'like', '%'.$filters['event'].'%');
        }
        if ($filters['cod_aluno'] !== '') {
            $q->where('payload->aluno_id', $filters['cod_aluno']);
        }
        if ($filters['idpes'] !== '') {
            $q->where('payload->idpes', $filters['idpes']);
        }

        $now = now();
        if ($filters['token_status'] === 'used') {
            $q->whereNotNull('used_at');
        } elseif ($filters['token_status'] === 'pending') {
            $q->whereNull('used_at');
        } elseif ($filters['token_status'] === 'expired') {
            $q->whereNotNull('expires_at')->where('expires_at', '<', $now);
        } elseif ($filters['token_status'] === 'valid') {
            $q->whereNull('used_at')->where(function ($qq) use ($now) {
                $qq->whereNull('expires_at')->orWhere('expires_at', '>=', $now);
            });
        }

        // Filtro por estado "Catraca (Gestor)" usando a última tentativa por request.
        if (in_array($filters['catraca'], ['ok', 'fail', 'none'], true)) {
            $latestAttemptIds = FacialEnrollAttempt::query()
                ->selectRaw('MAX(id) as latest_id, facial_send_request_id')
                ->groupBy('facial_send_request_id');

            $q->leftJoinSub($latestAttemptIds, 'la_max', function ($join) {
                $join->on('la_max.facial_send_request_id', '=', 'facial_send_requests.id');
            })
                ->leftJoin('facial_enroll_attempts as la', 'la.id', '=', 'la_max.latest_id')
                ->select('facial_send_requests.*');

            if ($filters['catraca'] === 'none') {
                $q->whereNull('la.id');
            } elseif ($filters['catraca'] === 'ok') {
                $q->whereNotNull('la.id')->where('la.ok', true);
            } elseif ($filters['catraca'] === 'fail') {
                $q->whereNotNull('la.id')->where('la.ok', false);
            }
        }

        $items = $q->orderByDesc('id')
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

        $codAlunos = $items->getCollection()
            ->map(fn (FacialSendRequest $it) => (string) (data_get($it->payload, 'aluno_id') ?? ''))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $guestLinksByCod = collect();
        if ($codAlunos !== []) {
            $guestLinksByCod = GestorGuestLink::query()
                ->whereIn('cod_aluno', $codAlunos)
                ->get()
                ->keyBy('cod_aluno');
        }

        $gestorHistoriesByRequest = collect();
        if ($ids->isNotEmpty()) {
            $gestorHistoriesByRequest = FacialGestorCatracaHistory::query()
                ->whereIn('facial_send_request_id', $ids)
                ->orderByDesc('id')
                ->get()
                ->groupBy('facial_send_request_id');
        }

        $ieducar = Integration::query()->where('key', 'ieducar')->first();
        $gestor = Integration::query()->where('key', 'gestor')->first();

        return view('admin.facial_requests', [
            'items' => $items,
            'perPage' => $perPage,
            'filters' => $filters,
            'attemptsByRequest' => $attemptsByRequest,
            'statusByRequest' => $statusByRequest,
            'guestLinksByCod' => $guestLinksByCod,
            'gestorHistoriesByRequest' => $gestorHistoriesByRequest,
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

        $payload = is_array($item->payload) ? $item->payload : [];
        $codAluno = (string) (data_get($payload, 'aluno_id') ?? '');
        $guestLink = $codAluno !== '' ? GestorGuestLink::query()->where('cod_aluno', $codAluno)->first() : null;
        $gestorHistories = FacialGestorCatracaHistory::query()
            ->where('facial_send_request_id', $item->id)
            ->orderByDesc('id')
            ->get();
        $lastEnrollHistory = $gestorHistories
            ->where('event_type', FacialGestorCatracaHistory::EVENT_ENROLL_RESPONSE)
            ->sortByDesc('id')
            ->first();
        $inviteIdForInspect = $guestLink?->invite_id
            ?? $gestorHistories
                ->where('event_type', FacialGestorCatracaHistory::EVENT_ENROLL_RESPONSE)
                ->whereNotNull('invite_id')
                ->sortByDesc('id')
                ->first()
                ?->invite_id;
        $showGestorInviteVerify = $inviteIdForInspect && ($item->used_at || $lastEnrollHistory);

        return view('admin.facial_request_show', [
            'item' => $item,
            'attempts' => $attempts,
            'snapshots' => $snapshots,
            'guestLink' => $guestLink,
            'gestorHistories' => $gestorHistories,
            'inviteIdForInspect' => $inviteIdForInspect,
            'showGestorInviteVerify' => $showGestorInviteVerify,
        ]);
    }

    public function inspectGestorInvite(int $id)
    {
        $item = FacialSendRequest::query()->findOrFail($id);
        $gestor = Integration::query()->where('key', 'gestor')->first();
        if (! $gestor) {
            return response()
                ->view('admin.facial_gestor_invite', [
                    'item' => $item,
                    'inviteId' => null,
                    'error' => 'Integração Gestor (key=gestor) não encontrada.',
                    'effectiveUrl' => null,
                    'httpStatus' => null,
                    'responseJson' => null,
                    'rawBody' => null,
                ], 503);
        }

        $payload = is_array($item->payload) ? $item->payload : [];
        $codAluno = (string) (data_get($payload, 'aluno_id') ?? '');
        $inviteId = $codAluno !== ''
            ? GestorGuestLink::query()->where('cod_aluno', $codAluno)->value('invite_id')
            : null;
        if (! $inviteId) {
            $inviteId = FacialGestorCatracaHistory::query()
                ->where('facial_send_request_id', $item->id)
                ->where('event_type', FacialGestorCatracaHistory::EVENT_ENROLL_RESPONSE)
                ->whereNotNull('invite_id')
                ->orderByDesc('id')
                ->value('invite_id');
        }

        if (! $inviteId) {
            return response()
                ->view('admin.facial_gestor_invite', [
                    'item' => $item,
                    'inviteId' => null,
                    'error' => 'Nenhum InviteId conhecido para este pedido (sincronize matrícula ou conclua envio facial com resposta da catraca).',
                    'effectiveUrl' => null,
                    'httpStatus' => null,
                    'responseJson' => null,
                    'rawBody' => null,
                ], 422);
        }

        $client = new GestorClient($gestor);
        $effectiveUrl = $client->inviteGetAbsoluteUrl($inviteId);

        try {
            $resp = $client->getInvite($inviteId);
            $httpStatus = $resp->status();
            $rawBody = (string) $resp->body();
            $responseJson = $resp->json();

            return response()->view('admin.facial_gestor_invite', [
                'item' => $item,
                'error' => null,
                'inviteId' => (int) $inviteId,
                'effectiveUrl' => $effectiveUrl,
                'httpStatus' => $httpStatus,
                'responseJson' => is_array($responseJson) ? $responseJson : null,
                'rawBody' => $rawBody,
            ], $resp->successful() ? 200 : $httpStatus);
        } catch (\Throwable $e) {
            return response()->view('admin.facial_gestor_invite', [
                'item' => $item,
                'error' => $e->getMessage(),
                'inviteId' => (int) $inviteId,
                'effectiveUrl' => $effectiveUrl,
                'httpStatus' => null,
                'responseJson' => null,
                'rawBody' => null,
            ], 500);
        }
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
