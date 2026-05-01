<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FacialSendRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class IeducarFacialRequestController extends Controller
{
    /**
     * iEducar chama para gerar URL/token de abertura da tela de envio facial.
     *
     * Auth: HMAC (verify.hmac:ieducar)
     */
    public function store(Request $request)
    {
        $eventId = (string) $request->attributes->get('event_id', '');
        if ($eventId === '') {
            return response()->json(['message' => 'Event id ausente.'], 400);
        }

        // "dados empacotados" pelo pacote do iEducar.
        $payload = $request->validate([
            'external_id' => ['required', 'string'], // id da pessoa no Gestor (ou referência)
            'aluno_id' => ['nullable', 'string'],
            'matricula_id' => ['nullable', 'string'],
            'idpes' => ['nullable', 'string'],
            'photo_url' => ['nullable', 'string'],
            'responsavel' => ['nullable', 'array'],
            'meta' => ['nullable', 'array'],
        ]);

        $ttlSeconds = (int) config('app.facial_request_ttl_seconds', 900);
        if ($ttlSeconds <= 0) {
            $ttlSeconds = 900;
        }

        $record = FacialSendRequest::query()->firstOrCreate(
            ['event_id' => $eventId],
            [
                'payload' => $payload,
                'token' => Str::random(48),
                'expires_at' => now()->addSeconds($ttlSeconds),
            ],
        );

        // Se existir mas estiver expirado, renova o token.
        if ($record->expires_at && $record->expires_at->isPast()) {
            $record->payload = $payload;
            $record->token = Str::random(48);
            $record->expires_at = now()->addSeconds($ttlSeconds);
            $record->used_at = null;
            $record->save();
        }

        // Usa o host da requisição (evita APP_URL apontando para host.docker.internal em ambientes docker).
        $url = rtrim((string) $request->root(), '/').'/facial/enviar?token='.urlencode($record->token);

        return response()
            ->json([
                'ok' => true,
                'token' => $record->token,
                'expires_at' => $record->expires_at?->toIso8601String(),
                // formato que o iEducar entende para abrir a tela de coleta
                'redirect_url' => $url,
                // compatibilidade com clientes antigos
                'url' => $url,
            ])
            ->header('Location', $url);
    }
}
