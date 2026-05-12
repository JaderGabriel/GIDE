<?php

namespace App\Services\Enrichment;

use App\Models\Integration;
use App\Models\StudentEnrichmentCache;
use App\Services\Ieducar\IeducarClient;
use Illuminate\Support\Facades\Log;
use Throwable;

class StudentEnrichmentService
{
    private const TTL_HOURS = 24;

    /**
     * Busca dados do aluno do cache ou do iEducar. Retorna null se indisponível.
     *
     * @return array<string, mixed>|null
     */
    public function enrich(int $codAluno): ?array
    {
        if ($codAluno < 1) {
            return null;
        }

        $cached = StudentEnrichmentCache::query()
            ->where('cod_aluno', $codAluno)
            ->fresh()
            ->first();

        if ($cached) {
            return $cached->data;
        }

        return $this->fetchAndCache($codAluno);
    }

    /**
     * Força busca nova no iEducar ignorando cache.
     *
     * @return array<string, mixed>|null
     */
    public function refresh(int $codAluno): ?array
    {
        if ($codAluno < 1) {
            return null;
        }

        return $this->fetchAndCache($codAluno);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchAndCache(int $codAluno): ?array
    {
        $ieducar = Integration::query()->where('key', 'ieducar')->where('enabled', true)->first();
        if (! $ieducar) {
            return null;
        }

        try {
            $client = new IeducarClient($ieducar);
            $response = $client->postCatracaFrequenciaAlunoConsulta([
                'cod_aluno' => $codAluno,
            ]);

            if (! $response->successful()) {
                Log::debug('student_enrichment.fetch_failed', [
                    'cod_aluno' => $codAluno,
                    'http_status' => $response->status(),
                ]);

                return null;
            }

            $body = $response->json();
            if (! is_array($body)) {
                return null;
            }

            $normalized = $this->normalize($body, $codAluno);

            StudentEnrichmentCache::query()->updateOrCreate(
                ['cod_aluno' => $codAluno],
                [
                    'data' => $normalized,
                    'fetched_at' => now(),
                    'expires_at' => now()->addHours(self::TTL_HOURS),
                ],
            );

            return $normalized;
        } catch (Throwable $e) {
            Log::debug('student_enrichment.fetch_exception', [
                'cod_aluno' => $codAluno,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function normalize(array $body, int $codAluno): array
    {
        $aluno = $body['aluno'] ?? $body;

        return [
            'cod_aluno' => $codAluno,
            'nome' => $aluno['nome'] ?? $aluno['name'] ?? $aluno['nm_aluno'] ?? null,
            'turma' => $aluno['turma'] ?? $aluno['nm_turma'] ?? null,
            'serie' => $aluno['serie'] ?? $aluno['nm_serie'] ?? null,
            'etapa' => $aluno['etapa'] ?? null,
            'instituicao_id' => $aluno['instituicao_id'] ?? $aluno['ref_cod_instituicao'] ?? null,
            'situacao' => $aluno['situacao'] ?? $aluno['situacao_matricula'] ?? null,
            'matricula_id' => $aluno['matricula_id'] ?? $aluno['cod_matricula'] ?? null,
        ];
    }
}
