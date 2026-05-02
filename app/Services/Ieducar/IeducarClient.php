<?php

namespace App\Services\Ieducar;

use App\Models\Integration;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class IeducarClient
{
    public const CAT_FREQUENCIA_CONFIRM_PATH = '/api/catraca-frequencia/gide/facial/confirmacao';

    public const CAT_FREQUENCIA_ALUNO_CONSULTA_PATH = '/api/catraca-frequencia/gide/aluno/consulta';

    public const CAT_FREQUENCIA_REGISTRO_PATH = '/api/catraca-frequencia/gide/frequencia/registro';

    public const CAT_FREQUENCIA_CONTRACT_VERSION = '1.0';

    public function __construct(private readonly Integration $integration) {}

    private function request(): PendingRequest
    {
        return Http::timeout(30);
    }

    private function requestBearer(string $token): PendingRequest
    {
        if ($token === '') {
            throw new \RuntimeException('Token Bearer do iEducar não configurado.');
        }

        return $this->request()->withToken($token);
    }

    private function url(string $resourcePath): string
    {
        return rtrim((string) ($this->integration->base_url ?? ''), '/').'/'.ltrim($resourcePath, '/');
    }

    public function getRegras(int $instituicaoId): Response
    {
        $accessKey = (string) data_get($this->integration->extra, 'access_key', '');
        if ($accessKey === '') {
            throw new \RuntimeException('access_key do iEducar não configurado em integrations.extra.access_key');
        }

        return $this->request()->get($this->url('/module/Api/Regra'), [
            'access_key' => $accessKey,
            'oper' => 'get',
            'resource' => 'regras',
            'instituicao_id' => $instituicaoId,
        ]);
    }

    public function postFaltasGeral(int $instituicaoId, int $etapa, array $turmas): Response
    {
        $accessKey = (string) data_get($this->integration->extra, 'access_key', '');
        if ($accessKey === '') {
            throw new \RuntimeException('access_key do iEducar não configurado em integrations.extra.access_key');
        }

        return $this->request()->asForm()->post($this->url('/module/Api/Diario'), [
            'access_key' => $accessKey,
            'oper' => 'post',
            'resource' => 'faltas-geral',
            'instituicao_id' => $instituicaoId,
            'etapa' => $etapa,
            'turmas' => $turmas,
        ]);
    }

    public function postFaltasPorComponente(int $instituicaoId, int $etapa, array $turmas): Response
    {
        $accessKey = (string) data_get($this->integration->extra, 'access_key', '');
        if ($accessKey === '') {
            throw new \RuntimeException('access_key do iEducar não configurado em integrations.extra.access_key');
        }

        return $this->request()->asForm()->post($this->url('/module/Api/Diario'), [
            'access_key' => $accessKey,
            'oper' => 'post',
            'resource' => 'faltas-por-componente',
            'instituicao_id' => $instituicaoId,
            'etapa' => $etapa,
            'turmas' => $turmas,
        ]);
    }

    /**
     * Callback de confirmação (GIDE → iEducar) após coleta efetivada no GIDE/Gestor.
     * Path fixo: /api/catraca-frequencia/gide/facial/confirmacao
     * Auth: Bearer token (integrations.extra.catraca_frequencia.confirmacao_token ou integrations.auth_token).
     */
    public function postCatracaFrequenciaFacialConfirmacao(array $payload): Response
    {
        $token = (string) data_get($this->integration->extra, 'catraca_frequencia.confirmacao_token', '');
        if ($token === '') {
            $token = (string) ($this->integration->auth_token ?? '');
        }

        if ($token === '') {
            throw new \RuntimeException('Token de confirmação do iEducar não configurado (integrations.extra.catraca_frequencia.confirmacao_token ou integrations.auth_token).');
        }

        $payload = array_merge([
            'meta' => [
                'contract_version' => self::CAT_FREQUENCIA_CONTRACT_VERSION,
            ],
        ], $payload);

        return $this->requestBearer($token)
            ->acceptJson()
            ->post($this->url(self::CAT_FREQUENCIA_CONFIRM_PATH), $payload);
    }

    /**
     * Consulta de aluno (GIDE → iEducar).
     * Path fixo: /api/catraca-frequencia/gide/aluno/consulta
     * Auth: Bearer token (mesmo critério da confirmação).
     */
    public function postCatracaFrequenciaAlunoConsulta(array $payload): Response
    {
        $token = (string) data_get($this->integration->extra, 'catraca_frequencia.confirmacao_token', '');
        if ($token === '') {
            $token = (string) ($this->integration->auth_token ?? '');
        }

        if ($token === '') {
            throw new \RuntimeException('Token para consulta do iEducar não configurado (integrations.extra.catraca_frequencia.confirmacao_token ou integrations.auth_token).');
        }

        $payload = array_merge([
            'meta' => [
                'contract_version' => self::CAT_FREQUENCIA_CONTRACT_VERSION,
            ],
        ], $payload);

        return $this->requestBearer($token)
            ->acceptJson()
            ->post($this->url(self::CAT_FREQUENCIA_ALUNO_CONSULTA_PATH), $payload);
    }

    /**
     * Registro de frequência em lote (GIDE → i-Educar).
     * Path: /api/catraca-frequencia/gide/frequencia/registro
     * Auth: mesmo Bearer da confirmação/consulta (confirmacao_token ou auth_token).
     *
     * @param  array<string, mixed>  $payload  Corpo v1 plano B (meta, fonte, presente, identificacao+data_ref ou registros[]).
     */
    public function postCatracaFrequenciaRegistro(array $payload): Response
    {
        $token = (string) data_get($this->integration->extra, 'catraca_frequencia.confirmacao_token', '');
        if ($token === '') {
            $token = (string) ($this->integration->auth_token ?? '');
        }

        if ($token === '') {
            throw new \RuntimeException('Token Bearer do iEducar não configurado (integrations.extra.catraca_frequencia.confirmacao_token ou integrations.auth_token).');
        }

        $meta = (array) ($payload['meta'] ?? []);
        if (! isset($meta['contract_version'])) {
            $meta['contract_version'] = self::CAT_FREQUENCIA_CONTRACT_VERSION;
        }
        $payload['meta'] = $meta;

        return $this->requestBearer($token)
            ->acceptJson()
            ->post($this->url(self::CAT_FREQUENCIA_REGISTRO_PATH), $payload);
    }
}
