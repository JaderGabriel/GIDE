<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Jobs\SendEnrollmentToAccessControl;
use App\Models\FacialEnrollAttempt;
use App\Models\FacialGestorCatracaHistory;
use App\Models\FacialSendRequest;
use App\Models\GestorGuestLink;
use App\Models\Integration;
use App\Services\Gestor\GestorClient;
use App\Services\Ieducar\IeducarClient;
use App\Services\Integrations\DeliveryRetryDispatcher;
use App\Services\Outbound\AccessControlOutboundService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Coleta e envio da foto ao Gestor: fluxo **síncrono** na requisição HTTP (sem fila),
 * pois a imagem não é armazenada no GIDE e o usuário aguarda o resultado na hora.
 * Pré-sync de matrícula/Invite aqui também é imediato; reenvios com backoff ficam em
 * {@see SendEnrollmentToAccessControl} / {@see DeliveryRetryDispatcher}.
 */
class FacialSendController extends Controller
{
    public function create(Request $request)
    {
        $token = (string) $request->query('token', '');
        if ($token === '') {
            abort(404);
        }

        $facialRequest = FacialSendRequest::query()->where('token', $token)->first();
        if (! $facialRequest) {
            abort(404);
        }
        if ($facialRequest->expires_at && $facialRequest->expires_at->isPast()) {
            return response()->view('facial.token_expired', [
                'expired_at' => $facialRequest->expires_at,
            ], 410);
        }
        if ($facialRequest->used_at) {
            abort(404);
        }

        $payload = is_array($facialRequest?->payload) ? $facialRequest->payload : [];

        $codAluno = (string) (data_get($payload, 'aluno_id') ?? '');
        $idpes = (string) (data_get($payload, 'idpes') ?? '');

        // Se o inbound já trouxe snapshot completo (novo contrato), usa direto para abrir a tela.
        $packedSnapshot = data_get($payload, 'ieducar_status');
        $ieducarStatus = is_array($packedSnapshot) ? $packedSnapshot : null;
        $ieducarStatusError = null;
        $ieducarIntegration = Integration::query()->where('key', 'ieducar')->first();
        try {
            if ($codAluno === '' && $idpes === '') {
                throw new \RuntimeException('Payload não contém aluno_id/idpes (não integrado).');
            }

            // Só consulta no iEducar se não veio snapshot no inbound.
            if (! $ieducarStatus) {
                if (! $ieducarIntegration) {
                    throw new \RuntimeException('Integração iEducar (key=ieducar) não configurada.');
                }

                $resp = (new IeducarClient($ieducarIntegration))->postCatracaFrequenciaAlunoConsulta([
                    'identificacao' => [
                        'cod_aluno' => $codAluno !== '' ? $codAluno : null,
                        'idpes' => $idpes !== '' ? $idpes : null,
                    ],
                ]);

                if (! $resp->successful()) {
                    $ieducarStatusError = 'HTTP '.$resp->status().': '.mb_substr((string) $resp->body(), 0, 2000);
                } else {
                    $ieducarStatus = $resp->json();
                }
            }
        } catch (\Throwable $e) {
            $ieducarStatusError = $e->getMessage();
        }

        return view('facial.send', [
            'request_token' => $token,
            'external_id' => data_get($payload, 'external_id') ?? null,
            'aluno_id' => data_get($payload, 'aluno_id') ?? null,
            'matricula_id' => data_get($payload, 'matricula_id') ?? null,
            'idpes' => data_get($payload, 'idpes') ?? null,
            'responsavel' => data_get($payload, 'responsavel'),
            'ieducar_status' => $ieducarStatus,
            'ieducar_status_error' => $ieducarStatusError,
            'facial_return_url' => auth()->check()
                ? url('/dashboard')
                : $this->ieducarPublicReturnUrl($ieducarIntegration),
        ]);
    }

    public function store(Request $request)
    {
        $isAjax = $request->header('X-Requested-With') === 'XMLHttpRequest' || $request->expectsJson();

        $data = $request->validate([
            'aluno_id' => ['nullable', 'string'],
            'matricula_id' => ['nullable', 'string'],
            'idpes' => ['nullable', 'string'],
            'photo_url' => ['nullable', 'string'],
            'external_id' => ['required', 'string'],
            // A coleta deve ser feita na hora (câmera). Não permitimos fallback por URL.
            'photo' => ['required', 'file', 'mimetypes:image/jpeg,image/png,image/webp', 'max:5120'],
            'request_token' => ['required', 'string'],
        ]);

        $ieducar = Integration::query()->where('key', 'ieducar')->first();
        $gestor = Integration::query()->where('key', 'gestor')->first();

        if (! $ieducar || ! $gestor) {
            if ($isAjax) {
                return response()->json(['ok' => false, 'message' => 'Integrações ieducar/gestor não configuradas.'], 422);
            }

            return back()->withErrors(['external_id' => 'Integrações ieducar/gestor não configuradas.']);
        }

        $facialRequest = FacialSendRequest::query()->where('token', $data['request_token'])->first();
        if (! $facialRequest || ($facialRequest->expires_at && $facialRequest->expires_at->isPast()) || $facialRequest->used_at) {
            if ($isAjax) {
                return response()->json(['ok' => false, 'message' => 'Envio bloqueado: token inválido/expirado/consumido.'], 422);
            }

            return back()->withErrors(['external_id' => 'Envio bloqueado: esta tela só pode ser usada via fluxo do iEducar (token inválido/expirado/consumido).']);
        }

        // Força "dados empacotados" como fonte de verdade (evita envio fora do fluxo).
        $packed = is_array($facialRequest->payload) ? $facialRequest->payload : [];
        $data['external_id'] = (string) (data_get($packed, 'external_id') ?? $data['external_id']);
        $data['aluno_id'] = (string) (data_get($packed, 'aluno_id') ?? ($data['aluno_id'] ?? ''));
        $data['matricula_id'] = (string) (data_get($packed, 'matricula_id') ?? ($data['matricula_id'] ?? ''));
        $data['idpes'] = (string) (data_get($packed, 'idpes') ?? ($data['idpes'] ?? ''));
        $data['photo_url'] = (string) (data_get($packed, 'photo_url') ?? ($data['photo_url'] ?? ''));

        try {
            $uploaded = $request->file('photo');
            $gestorResp = null;
            // upload vem do blob capturado (memória) via FormData
            $mime = (string) ($uploaded?->getMimeType() ?? 'image/jpeg');
            $stream = $uploaded ? fopen($uploaded->getRealPath(), 'r') : false;
            if ($stream === false) {
                throw new \RuntimeException('Falha ao abrir stream da foto.');
            }

            // Pré-check: tenta garantir que o usuário/aluno está cadastrado no Gestor antes do enroll facial.
            // Se houver payload completo do iEducar no request (webhook) e existir endpoint de sync, dispara 1 tentativa.
            try {
                $packed = is_array($facialRequest->payload) ? $facialRequest->payload : [];
                $snapshot = data_get($packed, 'ieducar_status');
                $syncPath = (string) data_get($gestor->extra, 'endpoints.enrollment_sync_path', '');
                if ($syncPath !== '' && is_array($snapshot)) {
                    $eventId = (string) ($facialRequest->event_id ?? ('facial_send_request:'.$facialRequest->id));
                    $delivery = (new AccessControlOutboundService)->sendEnrollmentPayload($eventId, $snapshot);
                    if (! $delivery->delivered_at) {
                        throw new \RuntimeException('Cadastro do usuário no Gestor ainda não sincronizado. Detalhe: '.($delivery->last_error ?? 'sem detalhe'));
                    }
                }
            } catch (\Throwable $e) {
                // Se falhar sincronização, aborta antes do enroll para evitar erro confuso.
                if (is_resource($stream)) {
                    fclose($stream);
                }
                throw $e;
            }

            // Busca guest_id para fazer Face Create.
            $codAluno = (string) ($data['aluno_id'] ?? '');
            if ($codAluno === '') {
                throw new \RuntimeException('aluno_id ausente: não é possível localizar Guest no Gestor.');
            }

            $link = GestorGuestLink::query()->where('cod_aluno', $codAluno)->first();
            $guestId = $link?->guest_id;
            if (! $guestId) {
                // Fallback: tenta descobrir via API (list invites e bate pelo name do guest).
                $gestorClientTmp = new GestorClient($gestor);
                $listResp = $gestorClientTmp->listInvites(300);
                $json = $listResp->json();
                $found = null;
                $inviteIdFromList = null;
                if (is_array($json)) {
                    $list = array_is_list($json) ? $json : (data_get($json, 'data') ?? data_get($json, 'items') ?? $json);
                    if (is_array($list)) {
                        foreach ($list as $inv) {
                            if (! is_array($inv)) {
                                continue;
                            }
                            $guests = data_get($inv, 'guests') ?? data_get($inv, 'Guests');
                            if (! is_array($guests)) {
                                continue;
                            }
                            foreach ($guests as $g) {
                                if (! is_array($g)) {
                                    continue;
                                }
                                $gName = (string) (data_get($g, 'name') ?? data_get($g, 'Name') ?? '');
                                if ($gName === $codAluno) {
                                    $found = data_get($g, 'id') ?? data_get($g, 'Id') ?? null;
                                    $inviteIdFromList = data_get($inv, 'id') ?? data_get($inv, 'Id') ?? data_get($inv, 'inviteId') ?? data_get($inv, 'InviteId');
                                    break 2;
                                }
                            }
                        }
                    }
                }

                if (is_numeric($found)) {
                    $guestId = (int) $found;
                    $link = $link ?: GestorGuestLink::query()->firstOrCreate(['cod_aluno' => $codAluno], ['cod_aluno' => $codAluno]);
                    $link->guest_id = $guestId;
                    if (is_numeric($inviteIdFromList)) {
                        $link->invite_id = (int) $inviteIdFromList;
                    }
                    $link->last_error = null;
                    $link->save();
                }

                if (! $guestId) {
                    throw new \RuntimeException('GuestId não encontrado para este aluno. O Invite Create não retornou guest_id e a listagem de Invites não encontrou guest com name='.$codAluno.'.');
                }
            }

            $gestorClient = new GestorClient($gestor);
            $gestorResp = $gestorClient->createGuestFace((int) $guestId, $stream, $mime);
            $faceEffectiveUrl = $gestorClient->guestFaceEnrollAbsoluteUrl((int) $guestId);

            // Auditoria no link
            try {
                if ($link) {
                    $link->last_face_http_status = (int) $gestorResp->status();
                    $link->last_face_response_body = mb_substr((string) $gestorResp->body(), 0, 20000);
                    $link->last_error = $gestorResp->successful() ? null : ('HTTP '.$gestorResp->status());
                    $link->save();
                }
            } catch (\Throwable) {
            }

            if (is_resource($stream)) {
                fclose($stream);
            }

            // Auditoria: resposta do sistema da catraca (Gestor)
            FacialEnrollAttempt::query()->create([
                'facial_send_request_id' => $facialRequest->id,
                'external_id' => $data['external_id'] ?? null,
                'ok' => method_exists($gestorResp, 'successful') ? (bool) $gestorResp->successful() : false,
                'http_status' => method_exists($gestorResp, 'status') ? (int) $gestorResp->status() : null,
                'response_body' => method_exists($gestorResp, 'body') ? mb_substr((string) $gestorResp->body(), 0, 20000) : null,
                'error_message' => null,
            ]);

            $linkFresh = GestorGuestLink::query()->where('cod_aluno', $codAluno)->first();
            FacialGestorCatracaHistory::recordEnrollResponse(
                $facialRequest,
                $codAluno,
                $linkFresh?->invite_id,
                (int) $guestId,
                $gestorResp,
                $faceEffectiveUrl,
            );

            $enrollOk = $gestorResp && method_exists($gestorResp, 'successful') && $gestorResp->successful();

            // Se o enroll no Gestor foi aceito, calcula validade e informa ao iEducar (quando configurado).
            if ($enrollOk) {
                $facialRequest->used_at = now();
                $facialRequest->save();

                // Confirmação de coleta (GIDE → iEducar) — contrato da integração catraca-frequência.
                // Se o token não estiver configurado, o client lança exceção (falha explícita).
                // `idpes` é obrigatório no iEducar para confirmar.
                if (($data['idpes'] ?? '') !== '') {
                    (new IeducarClient($ieducar))->postCatracaFrequenciaFacialConfirmacao([
                        'identificacao' => [
                            'cod_aluno' => ($data['aluno_id'] ?? '') !== '' ? $data['aluno_id'] : null,
                            'idpes' => $data['idpes'],
                        ],
                        'data_coleta' => now()->toIso8601String(),
                    ]);
                }
            }

            if (! $enrollOk) {
                $failMsg = 'Envio não aceito pelo Gestor.';
                if ($gestorResp && method_exists($gestorResp, 'body')) {
                    $failMsg = 'HTTP '.(method_exists($gestorResp, 'status') ? (string) $gestorResp->status() : '?').': '
                        .mb_substr((string) $gestorResp->body(), 0, 800);
                }
                if ($isAjax) {
                    return response()->json(['ok' => false, 'message' => $failMsg], 422);
                }

                return back()->withErrors(['external_id' => $failMsg]);
            }

            $redirectUrl = $this->facialSuccessRedirectUrl($ieducar);
            $statusMsg = 'Facial enviado com sucesso.';

            if ($isAjax) {
                return response()->json([
                    'ok' => true,
                    'message' => $statusMsg,
                    'redirect_url' => $redirectUrl,
                ]);
            }

            return $this->redirectAfterFacialSuccess($redirectUrl, $statusMsg);
        } catch (\Throwable $e) {
            // Auditoria do erro (sem resposta HTTP do Gestor)
            try {
                FacialEnrollAttempt::query()->create([
                    'facial_send_request_id' => $facialRequest->id,
                    'external_id' => $data['external_id'] ?? null,
                    'ok' => false,
                    'http_status' => null,
                    'response_body' => null,
                    'error_message' => $e->getMessage(),
                ]);
            } catch (\Throwable) {
                // evita mascarar erro original
            }

            if ($isAjax) {
                return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['external_id' => $e->getMessage()]);
        }
    }

    /**
     * Após envio facial bem-sucedido: utilizador autenticado → dashboard GIDE; convidado → base do iEducar (integração).
     */
    private function facialSuccessRedirectUrl(Integration $ieducar): string
    {
        if (auth()->check()) {
            return url('/dashboard');
        }

        return $this->ieducarPublicReturnUrl($ieducar);
    }

    private function ieducarPublicReturnUrl(?Integration $ieducar): string
    {
        $base = $ieducar ? trim((string) ($ieducar->base_url ?? '')) : '';
        if ($base !== '' && preg_match('#^https?://#i', $base) === 1) {
            return rtrim($base, '/');
        }

        return url('/');
    }

    private function redirectAfterFacialSuccess(string $redirectUrl, string $statusMsg): RedirectResponse
    {
        $dashboardUrl = url('/dashboard');
        if ($redirectUrl === $dashboardUrl) {
            return redirect()->to($dashboardUrl)->with('status', $statusMsg);
        }

        if (preg_match('#^https?://#i', $redirectUrl) === 1) {
            return redirect()->away($redirectUrl);
        }

        return redirect()->to($redirectUrl)->with('status', $statusMsg);
    }
}
