<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FacialGestorCatracaHistory;
use App\Models\FacialSendRequest;
use App\Models\GideFacialInbound;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GideFacialInboundController extends Controller
{
    public function nova(Request $request)
    {
        return $this->storeOperation($request, 'nova');
    }

    public function excluir(Request $request)
    {
        return $this->storeOperation($request, 'excluir');
    }

    private function storeOperation(Request $request, string $operation)
    {
        // Valida campos mínimos, mas persiste o JSON completo (novo contrato).
        $validated = $request->validate([
            'meta' => ['nullable', 'array'],
            'identificacao' => ['required', 'array'],
            'identificacao.cod_aluno' => ['nullable', 'string', 'max:32'],
            'identificacao.idpes' => ['nullable', 'string', 'max:32'],
            // dados opcionais (mas necessários para abrir a tela de coleta com envio ao Gestor)
            'external_id' => ['nullable', 'string', 'max:128'],
            'matricula_id' => ['nullable', 'string', 'max:64'],
            'photo_url' => ['nullable', 'string'],
            // Novo contrato: aceitar snapshot completo (sem validar campo-a-campo aqui).
            'aluno' => ['nullable', 'array'],
            'pessoa' => ['nullable', 'array'],
            'documentos' => ['nullable', 'array'],
            'fisica' => ['nullable', 'array'],
            'matricula' => ['nullable', 'array'],
        ]);
        $payload = $request->all();

        $codAluno = (string) (data_get($validated, 'identificacao.cod_aluno') ?? '');
        $idpes = (string) (data_get($validated, 'identificacao.idpes') ?? '');

        // Ao menos uma chave precisa existir para permitir dedupe e rastreio.
        if ($codAluno === '' && $idpes === '') {
            return response()->json(['message' => 'identificacao.cod_aluno ou identificacao.idpes é obrigatório.'], 422);
        }

        $emittedAt = (string) (data_get($validated, 'meta.emitted_at') ?? data_get($payload, 'meta.emitted_at') ?? '');
        $raw = $request->getContent();
        $dedupeKey = hash('sha256', implode('|', [
            $operation,
            $codAluno,
            $idpes,
            $emittedAt,
            // fallback: ajuda a dedupe quando emitted_at não vier (mantém idempotência em reenvio do mesmo body)
            hash('sha256', $raw),
        ]));

        $record = GideFacialInbound::query()->firstOrCreate(
            ['dedupe_key' => $dedupeKey],
            [
                'operation' => $operation,
                'cod_aluno' => $codAluno !== '' ? $codAluno : null,
                'idpes' => $idpes !== '' ? $idpes : null,
                'payload' => $payload,
                'received_at' => now(),
                'status' => 'received',
            ],
        );

        // Para operação "nova", devolve URL/token para abrir a tela de coleta no GIDE (formato entendido pelo iEducar).
        if ($operation === 'nova') {
            $externalId = (string) (data_get($validated, 'external_id') ?? data_get($payload, 'external_id') ?? '');
            if ($externalId === '') {
                return response()->json([
                    'message' => 'Validação falhou.',
                    'errors' => [
                        'external_id' => ['Campo obrigatório para abrir a tela de coleta (não integrado no payload).'],
                    ],
                ], 422);
            }

            $ttlSeconds = (int) config('app.facial_request_ttl_seconds', 900);
            if ($ttlSeconds <= 0) {
                $ttlSeconds = 900;
            }

            $eventId = (string) (data_get($validated, 'meta.event_id') ?? data_get($payload, 'meta.event_id') ?? '');
            if ($eventId === '') {
                $eventId = 'catraca-frequencia:'.Str::uuid()->toString();
            }

            $matriculaId = (string) (data_get($validated, 'matricula_id')
                ?? data_get($payload, 'matricula_id')
                ?? data_get($payload, 'matricula.cod_matricula')
                ?? data_get($payload, 'matricula.cod_matricula')
                ?? '');

            $sendRequestPayload = [
                'external_id' => $externalId,
                'aluno_id' => $codAluno !== '' ? $codAluno : null,
                'idpes' => $idpes !== '' ? $idpes : null,
                'matricula_id' => $matriculaId,
                'photo_url' => (string) (data_get($validated, 'photo_url') ?? data_get($payload, 'photo_url') ?? ''),
                'meta' => (array) (data_get($validated, 'meta') ?? data_get($payload, 'meta') ?? []),
                // Snapshot do iEducar enviado no evento (novo contrato).
                'ieducar_status' => $payload,
            ];

            $sendReq = FacialSendRequest::query()->firstOrCreate(
                ['event_id' => $eventId],
                [
                    'payload' => $sendRequestPayload,
                    'token' => Str::random(48),
                    'expires_at' => now()->addSeconds($ttlSeconds),
                ],
            );

            // Se existir mas estiver expirado, renova o token.
            if ($sendReq->expires_at && $sendReq->expires_at->isPast()) {
                $sendReq->payload = $sendRequestPayload;
                $sendReq->token = Str::random(48);
                $sendReq->expires_at = now()->addSeconds($ttlSeconds);
                $sendReq->used_at = null;
                $sendReq->save();
            }

            if ($codAluno !== '') {
                FacialGestorCatracaHistory::recordSolicitacao($sendReq, $codAluno);
            }

            // Usa o host da requisição (evita APP_URL apontando para host.docker.internal em ambientes docker).
            $url = rtrim((string) $request->root(), '/').'/facial/enviar?token='.urlencode($sendReq->token);

            return response()
                ->json([
                    'ok' => true,
                    'created' => $record->wasRecentlyCreated,
                    'token' => $sendReq->token,
                    'expires_at' => $sendReq->expires_at?->toIso8601String(),
                    'redirect_url' => $url,
                    'url' => $url,
                ])
                ->header('Location', $url);
        }

        // "excluir": apenas ack (2xx) e auditoria.
        return response()->json([
            'ok' => true,
            'created' => $record->wasRecentlyCreated,
        ]);
    }
}
