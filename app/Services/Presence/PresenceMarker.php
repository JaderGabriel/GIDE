<?php

namespace App\Services\Presence;

use App\Models\Integration;
use App\Services\Ieducar\IeducarClient;

class PresenceMarker
{
    public function mark(Integration $ieducarIntegration, array $analysis): array
    {
        // MVP: apenas define o "contrato" de como seria lançado.
        // Para lançar de fato precisamos: instituicao_id, etapa, turma_id e/ou componentes + aluno_id.
        $instituicaoId = (int) (data_get($analysis, 'instituicao_id') ?? 0);
        $etapa = (int) (data_get($analysis, 'etapa') ?? 0);
        $turmas = data_get($analysis, 'turmas');

        if ($instituicaoId <= 0 || $etapa <= 0 || ! is_array($turmas)) {
            return [
                'status' => 'skipped',
                'reason' => 'Dados insuficientes para lançar faltas no iEducar (instituicao_id/etapa/turmas).',
            ];
        }

        $client = new IeducarClient($ieducarIntegration);

        $tipoPresenca = (int) (data_get($analysis, 'tipo_presenca') ?? 1);
        $resp = $tipoPresenca === 2
            ? $client->postFaltasPorComponente($instituicaoId, $etapa, $turmas)
            : $client->postFaltasGeral($instituicaoId, $etapa, $turmas);

        return [
            'status' => $resp->successful() ? 'ok' : 'error',
            'http_status' => $resp->status(),
            'body' => $resp->json(),
        ];
    }
}
