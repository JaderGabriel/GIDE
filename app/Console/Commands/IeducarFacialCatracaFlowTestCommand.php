<?php

namespace App\Console\Commands;

use App\Models\Integration;
use App\Services\Ieducar\IeducarClient;
use App\Support\Ieducar\GideFrequenciaRegistroPlanB;
use App\Support\Ieducar\IeducarFrequenciaPreviewMode;
use Illuminate\Console\Command;
use Throwable;

/**
 * Testa em sequência os endpoints catraca-frequência usados após coleta facial (consulta, confirmação)
 * e um registro de frequência “com facial” (fonte gide, presente), com meta.preview alinhado ao Gestor.
 *
 * @see docs/IEDUCAR_FACIAL_CATRACA_FLOW_TEST.md
 */
class IeducarFacialCatracaFlowTestCommand extends Command
{
    protected $signature = 'ieducar:facial-catraca-flow:test
                            {cod_aluno : Código do aluno no iEducar (inteiro ≥1; usado em consulta e no Plan B de frequência)}
                            {--idpes= : IDPES (obrigatório se não usar --skip-confirmacao; mesmo contrato da tela /facial/enviar)}
                            {--data-ref= : Data Y-m-d para data_ref da frequência (default: hoje no fuso da app)}
                            {--skip-consulta : Não chama POST /api/catraca-frequencia/gide/aluno/consulta}
                            {--skip-confirmacao : Não chama POST /api/catraca-frequencia/gide/facial/confirmacao}
                            {--skip-frequencia : Não chama POST /api/catraca-frequencia/gide/frequencia/registro}
                            {--force-preview : Força meta.preview=true no registro de frequência (ignora flag do Gestor)}
                            {--force-apply : Força meta.preview=false (sobrepõe --force-preview e a flag do Gestor)}
                            {--dry-run : Imprime URLs lógicas, meta.preview e JSONs; não executa HTTP}';

    protected $description = 'Testa fluxo iEducar pós-facial: consulta aluno, confirmação de coleta e registro de frequência (preview/homolog via Gestor)';

    public function handle(): int
    {
        $codArg = trim((string) $this->argument('cod_aluno'));
        if ($codArg === '' || ! ctype_digit($codArg) || (int) $codArg < 1) {
            $this->error('cod_aluno deve ser um inteiro ≥ 1.');

            return self::FAILURE;
        }
        $codInt = (int) $codArg;
        $codStr = (string) $codInt;

        $idpes = trim((string) ($this->option('idpes') ?? ''));
        $skipConsulta = (bool) $this->option('skip-consulta');
        $skipConfirmacao = (bool) $this->option('skip-confirmacao');
        $skipFrequencia = (bool) $this->option('skip-frequencia');
        $dryRun = (bool) $this->option('dry-run');
        $forcePreview = (bool) $this->option('force-preview');
        $forceApply = (bool) $this->option('force-apply');

        if (! $skipConfirmacao && $idpes === '') {
            $this->error('Informe --idpes ou use --skip-confirmacao. O iEducar exige idpes na confirmação facial (igual à tela de envio).');

            return self::FAILURE;
        }

        $ieducar = Integration::query()->where('key', 'ieducar')->first();
        if (! $ieducar) {
            $this->error('Integração iEducar (key=ieducar) não encontrada. Configure em /integracoes/ieducar.');

            return self::FAILURE;
        }
        if (! $dryRun && ! $ieducar->enabled) {
            $this->error('Integração iEducar desabilitada. Habilite em /integracoes/ieducar ou use --dry-run.');

            return self::FAILURE;
        }

        $gestor = Integration::query()->where('key', 'gestor')->first();
        $gestorEnv = data_get($gestor?->extra, 'ieducar_processing.environment');
        $gestorEnvStr = is_string($gestorEnv) ? $gestorEnv : null;

        $metaPreview = IeducarFrequenciaPreviewMode::resolveMetaPreview($gestorEnvStr, $forcePreview, $forceApply);
        $forced = $forcePreview || $forceApply;
        $previewExplain = IeducarFrequenciaPreviewMode::explain($metaPreview, $gestorEnvStr, $forced);

        $this->info('Fluxo de teste: catraca-frequência (GIDE → iEducar)');
        $this->line('Documentação: docs/IEDUCAR_FACIAL_CATRACA_FLOW_TEST.md');
        $this->newLine();
        $this->line('Parâmetros efetivos:');
        $this->line('  cod_aluno: '.$codStr);
        $this->line('  idpes: '.($idpes !== '' ? $idpes : '(omitido)'));
        $this->line('  Gestor extra.ieducar_processing.environment: '.IeducarFrequenciaPreviewMode::gestorEnvironmentLabel($gestorEnvStr));
        $this->line('  Frequência — '.$previewExplain);
        if (! $metaPreview) {
            $this->warn('  Atenção: meta.preview=false pode persistir frequência no i-Educar apontado por integrations.base_url.');
        }
        $this->newLine();

        $dataRef = (string) ($this->option('data-ref') ?? '');
        if ($dataRef === '') {
            $dataRef = now()->format('Y-m-d');
        }
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataRef)) {
            $this->error('--data-ref deve estar no formato Y-m-d.');

            return self::FAILURE;
        }

        $client = new IeducarClient($ieducar);
        $base = rtrim((string) ($ieducar->base_url ?? ''), '/');
        $exit = self::SUCCESS;

        if (! $skipConsulta) {
            $this->warn('── 1) Aluno — consulta ──');
            $payload = [
                'identificacao' => [
                    'cod_aluno' => $codStr,
                    'idpes' => $idpes !== '' ? $idpes : null,
                ],
            ];
            if ($dryRun) {
                $this->line('POST '.$base.IeducarClient::CAT_FREQUENCIA_ALUNO_CONSULTA_PATH);
                $this->line(json_encode(array_merge(['meta' => ['contract_version' => IeducarClient::CAT_FREQUENCIA_CONTRACT_VERSION]], $payload), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
            } else {
                try {
                    $resp = $client->postCatracaFrequenciaAlunoConsulta($payload);
                    $this->line('HTTP '.$resp->status());
                    $this->line(mb_substr((string) $resp->body(), 0, 12_000));
                    if (! $resp->successful()) {
                        $exit = self::FAILURE;
                    }
                } catch (Throwable $e) {
                    $this->error($e->getMessage());
                    $exit = self::FAILURE;
                }
            }
            $this->newLine();
        }

        if (! $skipConfirmacao) {
            $this->warn('── 2) Facial — confirmação de coleta ──');
            $payload = [
                'identificacao' => [
                    'cod_aluno' => $codStr,
                    'idpes' => $idpes,
                ],
                'data_coleta' => now()->toIso8601String(),
            ];
            if ($dryRun) {
                $this->line('POST '.$base.IeducarClient::CAT_FREQUENCIA_CONFIRM_PATH);
                $this->line(json_encode(array_merge(['meta' => ['contract_version' => IeducarClient::CAT_FREQUENCIA_CONTRACT_VERSION]], $payload), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
            } else {
                try {
                    $resp = $client->postCatracaFrequenciaFacialConfirmacao($payload);
                    $this->line('HTTP '.$resp->status());
                    $this->line(mb_substr((string) $resp->body(), 0, 12_000));
                    if (! $resp->successful()) {
                        $exit = self::FAILURE;
                    }
                } catch (Throwable $e) {
                    $this->error($e->getMessage());
                    $exit = self::FAILURE;
                }
            }
            $this->newLine();
        }

        if (! $skipFrequencia) {
            $this->warn('── 3) Frequência — registro (fonte=gide, presente=true, facial) ──');
            $row = [
                'meta' => [
                    'contract_version' => IeducarClient::CAT_FREQUENCIA_CONTRACT_VERSION,
                ],
                'fonte' => 'gide',
                'presente' => true,
                'identificacao' => [
                    'cod_aluno' => $codInt,
                ],
                'data_ref' => $dataRef,
            ];
            if ($idpes !== '' && ctype_digit($idpes)) {
                $row['identificacao']['idpes'] = (int) $idpes;
            }
            try {
                $normalized = GideFrequenciaRegistroPlanB::validateAndNormalize($row);
            } catch (Throwable $e) {
                $this->error('Payload frequência inválido: '.$e->getMessage());

                return self::FAILURE;
            }
            $normalized = GideFrequenciaRegistroPlanB::refreshDataRefsWithRandomClock($normalized);
            $meta = (array) ($normalized['meta'] ?? []);
            $meta['contract_version'] = IeducarClient::CAT_FREQUENCIA_CONTRACT_VERSION;
            $meta['preview'] = $metaPreview;
            $normalized['meta'] = $meta;

            if ($dryRun) {
                $this->line('POST '.$base.IeducarClient::CAT_FREQUENCIA_REGISTRO_PATH);
                $this->line(json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
            } else {
                try {
                    $resp = $client->postCatracaFrequenciaRegistro($normalized);
                    $this->line('HTTP '.$resp->status());
                    $this->line(mb_substr((string) $resp->body(), 0, 12_000));
                    if (! $resp->successful()) {
                        $exit = self::FAILURE;
                    }
                } catch (Throwable $e) {
                    $this->error($e->getMessage());
                    $exit = self::FAILURE;
                }
            }
            $this->newLine();
        }

        if ($dryRun) {
            $this->info('Dry-run: nenhuma requisição HTTP enviada.');
        } elseif ($exit === self::SUCCESS) {
            $this->info('Fluxo concluído: todas as etapas executadas retornaram HTTP de sucesso.');
        } else {
            $this->warn('Fluxo terminou com pelo menos uma resposta HTTP de falha ou exceção. Verifique o corpo acima e a integração iEducar.');
        }

        return $exit;
    }
}
