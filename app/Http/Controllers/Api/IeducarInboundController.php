<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendEnrollmentToAccessControl;
use App\Models\EnrollmentIngest;
use App\Models\Integration;
use Illuminate\Http\Request;

class IeducarInboundController extends Controller
{
    public function store(Request $request)
    {
        $eventId = (string) $request->attributes->get('event_id', '');
        if ($eventId === '') {
            return response()->json(['message' => 'Event id ausente.'], 400);
        }

        $payload = $request->validate([
            'aluno_id' => ['nullable'],
            'matricula_id' => ['nullable'],
            'escola_id' => ['nullable'],
            'instituicao_id' => ['nullable'],
            'dados' => ['nullable', 'array'],
        ]);

        $record = EnrollmentIngest::query()->firstOrCreate(
            ['source' => 'ieducar', 'event_id' => $eventId],
            ['payload' => $payload, 'received_at' => now()],
        );

        // Se o evento é novo, dispara envio assíncrono ao sistema de controle de acesso (Gestor),
        // quando a integração estiver habilitada e endpoint configurado.
        if ($record->wasRecentlyCreated) {
            $gestorEnabled = Integration::query()->where('key', 'gestor')->where('enabled', true)->exists();
            if ($gestorEnabled) {
                SendEnrollmentToAccessControl::dispatch($eventId, $payload);
            }
        }

        return response()->json([
            'ok' => true,
            'created' => $record->wasRecentlyCreated,
        ]);
    }
}
