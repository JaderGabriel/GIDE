<?php

namespace App\Support\Ieducar;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Contrato v1 — apenas formato B (por aluno): i-Educar resolve turma/matrícula/turno.
 *
 * @see https://serventec.local/schemas/gide-frequencia-registro-v1.schema.json
 */
final class GideFrequenciaRegistroPlanB
{
    /**
     * @param  array<string, mixed>  $decoded
     * @return array<string, mixed>
     */
    public static function validateAndNormalize(array $decoded): array
    {
        if (array_key_exists('ref_cod_turma', $decoded)) {
            throw ValidationException::withMessages([
                'payload' => 'O GIDE aceita apenas o formato por aluno (sem ref_cod_turma). Use identificacao + data_ref ou registros[].',
            ]);
        }

        Validator::make($decoded, [
            'meta' => ['required', 'array'],
            'meta.contract_version' => ['required', 'string', 'in:1.0'],
            'meta.preview' => ['sometimes', 'boolean'],
            'fonte' => ['required', 'in:gide,outras'],
            'presente' => ['required', 'boolean'],
        ])->validate();

        $hasRegistros = isset($decoded['registros']) && is_array($decoded['registros']) && count($decoded['registros']) >= 1;
        $hasUnit = isset($decoded['identificacao'], $decoded['data_ref'])
            && is_array($decoded['identificacao'])
            && array_key_exists('cod_aluno', $decoded['identificacao']);

        if ($hasRegistros && $hasUnit) {
            throw ValidationException::withMessages([
                'payload' => 'Use só envio unitário (identificacao + data_ref na raiz) ou só lote (registros), não ambos.',
            ]);
        }

        if ($hasRegistros) {
            Validator::make($decoded, [
                'registros' => ['required', 'array', 'min:1', 'max:2000'],
                'registros.*.cod_aluno' => ['required', 'integer', 'min:1'],
                'registros.*.data_ref' => ['required', 'date'],
                'registros.*.fonte' => ['sometimes', 'in:gide,outras'],
                'registros.*.presente' => ['sometimes', 'boolean'],
            ])->validate();

            foreach ($decoded['registros'] as $idx => $row) {
                if (! is_array($row)) {
                    throw ValidationException::withMessages([
                        'payload' => "registros.{$idx} deve ser um objeto.",
                    ]);
                }
                if (array_key_exists('ref_cod_matricula', $row)) {
                    throw ValidationException::withMessages([
                        'payload' => 'Formato legado (ref_cod_matricula) não é aceito. Use cod_aluno e data_ref em cada linha.',
                    ]);
                }
            }

            unset($decoded['identificacao'], $decoded['data_ref']);
        } elseif ($hasUnit) {
            Validator::make($decoded, [
                'data_ref' => ['required', 'date'],
                'identificacao.cod_aluno' => ['required', 'integer', 'min:1'],
                'identificacao.idpes' => ['sometimes', 'integer', 'min:1'],
            ])->validate();

            unset($decoded['registros']);
        } else {
            throw ValidationException::withMessages([
                'payload' => 'Informe identificacao.cod_aluno e data_ref (envio unitário) ou registros[] (lote) com cod_aluno e data_ref por item.',
            ]);
        }

        return $decoded;
    }

    /**
     * Atualiza `data_ref` (unitário) e `registros[].data_ref` para data-hora ISO 8601 com offset
     * (ex.: {@see Carbon::toIso8601String}) e horário aleatório no mesmo dia civil já indicado.
     * Usado no job antes do POST ao i-Educar (preview e apply).
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function refreshDataRefsWithRandomClock(array $payload): array
    {
        if (isset($payload['data_ref'])) {
            $payload['data_ref'] = self::dataRefWithRandomClock($payload['data_ref']);
        }
        if (isset($payload['registros']) && is_array($payload['registros'])) {
            foreach (array_keys($payload['registros']) as $idx) {
                $row = &$payload['registros'][$idx];
                if (! is_array($row) || ! array_key_exists('data_ref', $row)) {
                    continue;
                }
                $row['data_ref'] = self::dataRefWithRandomClock($row['data_ref']);
            }
            unset($row);
        }

        return $payload;
    }

    /**
     * Mantém o dia civil de {@see $dataRef} e aplica hora/minuto/segundo aleatórios (0 … 23:59:59),
     * no fuso {@see config('app.timezone')}, em ISO 8601 com offset (RFC 3339).
     */
    private static function dataRefWithRandomClock(mixed $dataRef): string
    {
        $tz = (string) config('app.timezone', 'UTC');

        try {
            $day = Carbon::parse((string) $dataRef, $tz)->startOfDay();
        } catch (\Throwable) {
            $day = Carbon::now($tz)->startOfDay();
        }

        $at = $day->copy()->addSeconds(random_int(0, 86_399))->timezone($tz);

        return $at->toIso8601String();
    }
}
