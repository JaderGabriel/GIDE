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
                'identificacao' => [
                    'cod_aluno' => $codAluno,
                    'idpes' => null,
                ],
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

            Log::debug('student_enrichment.raw_response', [
                'cod_aluno' => $codAluno,
                'http_status' => $response->status(),
                'body_keys' => array_keys($body),
                'body_excerpt' => mb_substr(json_encode($body, JSON_UNESCAPED_UNICODE), 0, 2000),
            ]);

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
        return [
            'cod_aluno' => $codAluno,
            'nome' => data_get($body, 'pessoa.nome')
                ?? data_get($body, 'pessoa.nome_completo')
                ?? data_get($body, 'status.aluno.nome')
                ?? data_get($body, 'aluno.nome')
                ?? data_get($body, 'nome')
                ?? null,
            'curso' => data_get($body, 'matricula.curso')
                ?? data_get($body, 'status.matricula.curso')
                ?? data_get($body, 'status.matricula.curso_nome')
                ?? data_get($body, 'status.matricula.nm_curso')
                ?? data_get($body, 'curso')
                ?? null,
            'turma' => data_get($body, 'matricula.turma')
                ?? data_get($body, 'status.matricula.turma')
                ?? data_get($body, 'status.matricula.turma_nome')
                ?? data_get($body, 'status.matricula.nm_turma')
                ?? data_get($body, 'turma')
                ?? null,
            'serie' => data_get($body, 'matricula.serie')
                ?? data_get($body, 'status.matricula.serie')
                ?? data_get($body, 'status.matricula.nm_serie')
                ?? data_get($body, 'serie')
                ?? null,
            'etapa' => data_get($body, 'matricula.etapa')
                ?? data_get($body, 'status.matricula.etapa')
                ?? data_get($body, 'etapa')
                ?? null,
            'instituicao_id' => data_get($body, 'matricula.ref_cod_instituicao')
                ?? data_get($body, 'instituicao_id')
                ?? null,
            'situacao' => data_get($body, 'matricula.situacao_descricao')
                ?? data_get($body, 'status.matricula.situacao_descricao')
                ?? data_get($body, 'situacao')
                ?? null,
            'matricula_id' => data_get($body, 'matricula.cod_matricula')
                ?? data_get($body, 'status.matricula.cod_matricula')
                ?? data_get($body, 'cod_matricula')
                ?? null,
        ];
    }
}
