<?php

namespace App\Console\Commands;

use App\Jobs\ProcessGestorAccessEventDeliveryJob;
use App\Models\GestorAccessEventDelivery;
use App\Models\Integration;
use App\Models\SmsDelivery;
use App\Models\SmsTemplate;
use App\Support\GestorCatracaAccessToken;
use App\Support\SmsTemplateKey;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Simula um POST de catraca → presença → (preview) iEducar → SMS, com diagnóstico e JSON.
 */
class GideSimulateCatracaAccessPipelineCommand extends Command
{
    protected $signature = 'gide:simulate-catraca-access-pipeline
                            {--token= : Bearer em texto plano; senão GIDE_CATRACA_ACCESS_TOKEN}
                            {--cod-aluno=211 : Valor simulado em `name` (cod_aluno no motor)}
                            {--http : Enviar o POST por HTTP em vez do kernel interno}
                            {--url= : Base URL com --http (default: APP_URL)}
                            {--real-ieducar : Não usar Http::fake no POST ao iEducar (chama rede real)}
                            {--diagnose-only : Só checklist de configuração e requisitos; não envia POST}';

    protected $description = 'Simula o fluxo catraca → access-events → presença → iEducar (preview) → SMS, com etapas, JSON e lacunas de configuração';

    public function handle(): int
    {
        $this->titleBlock('GIDE — simulação do pipeline catraca → presença → iEducar → SMS');
        $this->line('Este comando percorre o mesmo caminho que o equipamento (Bearer) e mostra entradas/saídas.');
        $this->newLine();

        $diag = $this->runDiagnostics();
        $this->printDiagnosticsTable($diag);

        $blocking = array_values(array_filter($diag, fn (array $r) => ($r['nível'] ?? '') === 'bloqueio'));
        if ($blocking !== []) {
            $this->newLine();
            $this->error('Corrija os itens em nível «bloqueio» antes de simular o POST.');

            return self::FAILURE;
        }

        if ((bool) $this->option('diagnose-only')) {
            $this->newLine();
            $this->info('Modo --diagnose-only: nenhum POST foi enviado.');

            return self::SUCCESS;
        }

        $token = trim((string) ($this->option('token') ?: (string) env('GIDE_CATRACA_ACCESS_TOKEN', '')));
        if ($token === '') {
            $this->error('Informe --token=... ou defina GIDE_CATRACA_ACCESS_TOKEN no ambiente.');

            return self::FAILURE;
        }

        $gestor = Integration::query()->where('key', 'gestor')->first();
        if (! GestorCatracaAccessToken::checkPlainAgainstIntegration($token, $gestor)) {
            $this->warn('O token não confere com o hash na base: espere 401 no POST.');
        }

        $ieducar = Integration::query()->where('key', 'ieducar')->first();
        $fakeIeducar = ! (bool) $this->option('real-ieducar');
        if ($fakeIeducar && $ieducar && (bool) $ieducar->enabled) {
            $this->step('Http::fake', 'Pedidos HTTP ao iEducar (catraca-frequência/registro) e à API SMS serão simulados com respostas 200 de exemplo. Use --real-ieducar para rede real no iEducar (SMS continua sujeito ao provedor).');
            Http::fake(function (\Illuminate\Http\Client\Request $request) {
                $url = $request->url();
                if (str_contains($url, '/api/catraca-frequencia/gide/frequencia/registro')) {
                    return Http::response([
                        'ok' => true,
                        'simulated' => true,
                        'message' => 'Resposta fictícia do comando gide:simulate-catraca-access-pipeline',
                    ], 200, ['Content-Type' => 'application/json']);
                }
                if (str_contains($url, '/channels/sms/messages')) {
                    return Http::response([
                        'id' => 'gide-sim-sms-'.uniqid(),
                        'status' => 'SIMULATED',
                    ], 200, ['Content-Type' => 'application/json']);
                }

                return Http::response(['gide_pipeline_fake' => 'fallback', 'url' => $url], 200);
            });
        } elseif (! $fakeIeducar) {
            $this->step('Rede real', 'Http::fake desativado: o POST ao iEducar usa a base_url e o Bearer configurados.');
        }

        $codAluno = (string) $this->option('cod-aluno');
        $payload = $this->buildPayload($ieducar, $codAluno);
        $eventId = (string) ($payload['eventId'] ?? '');

        $this->step('Payload de entrada (JSON enviado ao webhook)', 'Campos camelCase; `name` mapeia para aluno no motor; `creationDate` tenta cair numa janela de presença do iEducar.');
        $this->printJson($payload);

        $this->step('POST /api/v1/catraca/access-events', 'Autenticação: Authorization: Bearer. Persistência: access_events + gestor_access_event_deliveries; motor de presença lê janelas em integrations(key=ieducar).extra.presence.');

        try {
            if ((bool) $this->option('http')) {
                $baseUrl = rtrim((string) ($this->option('url') ?: config('app.url', 'http://localhost')), '/');
                $resp = Http::withToken($token, 'Bearer')
                    ->acceptJson()
                    ->asJson()
                    ->post($baseUrl.'/api/v1/catraca/access-events', $payload);
                $status = $resp->status();
                $json = $resp->json();
            } else {
                /** @var Kernel $kernel */
                $kernel = app(Kernel::class);
                $request = Request::create('/api/v1/catraca/access-events', 'POST', [], [], [], [
                    'HTTP_ACCEPT' => 'application/json',
                    'CONTENT_TYPE' => 'application/json',
                    'HTTP_AUTHORIZATION' => 'Bearer '.$token,
                ], json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

                $response = $kernel->handle($request);
                $status = $response->getStatusCode();
                $decoded = json_decode((string) $response->getContent(), true);
                $json = is_array($decoded) ? $decoded : ['_raw' => (string) $response->getContent()];
                $kernel->terminate($request, $response);
            }
        } catch (Throwable $e) {
            $this->error('Falha ao invocar o webhook: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->step('Resposta JSON do webhook', 'HTTP '.$status);
        $this->printJson($json);

        if ($status !== 200 || ($json['ok'] ?? null) !== true) {
            $this->warn('O POST não concluiu com sucesso; as etapas seguintes podem estar incompletas.');

            return self::FAILURE;
        }

        $deliveryId = (int) ($json['delivery_id'] ?? 0);
        if ($deliveryId < 1) {
            $this->error('Resposta sem delivery_id válido.');

            return self::FAILURE;
        }

        $delivery = GestorAccessEventDelivery::query()->find($deliveryId);
        if ($delivery) {
            $this->step('Auditoria gestor_access_event_deliveries', 'Linha #'.$delivery->id);
            $this->line(sprintf(
                '  • processing_status: %s · access_event_novo: %s · ieducar_attempts: %s',
                $delivery->processing_status,
                $delivery->access_event_was_created ? 'sim' : 'não',
                (string) ($delivery->ieducar_attempts ?? 0),
            ));
            $this->line('  • analysis.action: '.(string) data_get($delivery->analysis_json, 'action', '—'));
            if (! empty($json['queued'])) {
                $this->line('  • iEducar enfileirado (queued=true): o preview corre num job.');
            }
        }

        if (! empty($json['queued']) && $deliveryId > 0) {
            $this->step('Job ProcessGestorAccessEventDeliveryJob', 'Execução síncrona para fechar o preview nesta mesma CLI.');
            try {
                ProcessGestorAccessEventDeliveryJob::dispatchSync($deliveryId);
            } catch (Throwable $e) {
                $this->error('Job falhou: '.$e->getMessage());
            }
            $delivery?->refresh();
            if ($delivery) {
                $this->line('  • Estado após job: '.$delivery->processing_status.' · HTTP iEducar: '.($delivery->ieducar_frequencia_http_status ?? '—'));
            }
        }

        $queueConn = (string) config('queue.default', 'sync');
        if ($queueConn !== 'sync') {
            $this->step('Fila de jobs', 'QUEUE_CONNECTION='.$queueConn.' — a drenar até 8 jobs (SMS pendentes).');
            for ($i = 0; $i < 8; $i++) {
                Artisan::call('queue:work', [
                    'connection' => $queueConn,
                    '--once' => true,
                    '--no-ansi' => true,
                ]);
                $out = trim((string) Artisan::output());
                if ($out === '' || str_contains(strtolower($out), 'no jobs')) {
                    break;
                }
            }
        } else {
            $this->line('Fila: QUEUE_CONNECTION=sync (jobs executam inline).');
        }

        $smsRows = SmsDelivery::query()->where('event_id', $eventId)->orderBy('id')->get();
        $this->step('SMS (sms_deliveries)', $smsRows->isEmpty() ? 'Nenhum registo para este event_id (integração SMS desligada, templates inativos ou sem mark_presence / access_event novo).' : (string) $smsRows->count().' linha(s).');
        foreach ($smsRows as $row) {
            $this->line(sprintf(
                '  • #%d template=%s status=%s to=%s',
                $row->id,
                $row->template_key,
                $row->status,
                $row->to,
            ));
        }

        $this->newLine();
        $this->titleBlock('Resumo');
        $this->line('• Admin auditoria access-events: /admin/gestor-access-events/'.$deliveryId);
        $this->line('• Admin SMS: /sms?event_id='.urlencode($eventId));
        if ($fakeIeducar) {
            $this->line('• iEducar e SMS HTTP foram simulados (Http::fake), salvo --real-ieducar.');
        }

        return self::SUCCESS;
    }

    private function titleBlock(string $title): void
    {
        $this->newLine();
        $this->line(str_repeat('═', min(72, strlen($title) + 8)));
        $this->info(' '.$title);
        $this->line(str_repeat('═', min(72, strlen($title) + 8)));
    }

    private function step(string $name, string $detail): void
    {
        $this->newLine();
        $this->components->twoColumnDetail($name, $detail);
    }

    /**
     * @return list<array{check: string, nível: string, estado: string, nota: string}>
     */
    private function runDiagnostics(): array
    {
        $rows = [];

        $gestor = Integration::query()->where('key', 'gestor')->first();
        if (! $gestor) {
            $rows[] = ['check' => 'Integração gestor (linha no banco)', 'nível' => 'bloqueio', 'estado' => 'ausente', 'nota' => 'Crie em Integrações → Gestor.'];
        } elseif (! $gestor->enabled) {
            $rows[] = ['check' => 'gestor.enabled', 'nível' => 'bloqueio', 'estado' => 'false', 'nota' => 'O webhook catraca devolve 403 se o Gestor estiver desligado.'];
        } elseif (! GestorCatracaAccessToken::isConfigured($gestor)) {
            $rows[] = ['check' => 'Token Bearer da catraca (hash em extra)', 'nível' => 'bloqueio', 'estado' => 'não configurado', 'nota' => 'Gere em Integrações → Gestor; sem isto o endpoint responde 503.'];
        } else {
            $rows[] = ['check' => 'Gestor + token catraca', 'nível' => 'ok', 'estado' => 'pronto', 'nota' => ''];
        }

        $ieducar = Integration::query()->where('key', 'ieducar')->first();
        if (! $ieducar) {
            $rows[] = ['check' => 'Integração ieducar', 'nível' => 'aviso', 'estado' => 'ausente', 'nota' => 'Sem iEducar o motor de presença não corre; ingest faz skip.'];
        } elseif (! $ieducar->enabled) {
            $rows[] = ['check' => 'ieducar.enabled', 'nível' => 'aviso', 'estado' => 'false', 'nota' => 'Sem iEducar ativo não há mark_presence nem POST catraca-frequência.'];
        } else {
            $base = trim((string) ($ieducar->base_url ?? ''));
            $rows[] = ['check' => 'ieducar.base_url', 'nível' => $base === '' ? 'bloqueio' : 'ok', 'estado' => $base === '' ? 'vazio' : 'definido', 'nota' => 'Obrigatório para IeducarClient.'];

            $bearer = trim((string) data_get($ieducar->extra, 'catraca_frequencia.confirmacao_token', ''));
            if ($bearer === '') {
                $bearer = trim((string) ($ieducar->auth_token ?? ''));
            }
            $rows[] = ['check' => 'Bearer catraca-frequência (extra.catraca_frequencia.confirmacao_token ou auth_token)', 'nível' => $bearer === '' ? 'bloqueio' : 'ok', 'estado' => $bearer === '' ? 'vazio' : 'definido', 'nota' => 'Usado em postCatracaFrequenciaRegistro.'];

            $windows = data_get($ieducar->extra, 'presence.windows', []);
            $rows[] = ['check' => 'Janelas de presença (ieducar.extra.presence.windows)', 'nível' => (is_array($windows) && count($windows) > 0) ? 'ok' : 'aviso', 'estado' => is_array($windows) ? (string) count($windows).' janela(s)' : 'inválido', 'nota' => 'Sem janelas o motor devolve action=ignore (sem SMS de presença nem POST iEducar).'];
        }

        $sms = Integration::query()->where('key', 'sms')->first();
        if (! $sms || ! $sms->enabled) {
            $rows[] = ['check' => 'Integração SMS', 'nível' => 'aviso', 'estado' => ! $sms ? 'ausente' : 'desligada', 'nota' => 'Sem SMS ativo os jobs SendPresenceSms não enviam.'];
        } else {
            $from = trim((string) data_get($sms->extra, 'from', ''));
            $rows[] = ['check' => 'SMS extra.from', 'nível' => $from === '' ? 'bloqueio' : 'ok', 'estado' => $from === '' ? 'vazio' : 'definido', 'nota' => 'Obrigatório para Zenvia.'];

            $catracaTpl = SmsTemplate::query()->where('key', SmsTemplateKey::PRESENCE_CATRACA)->where('enabled', true)->first()
                ?? SmsTemplate::query()->where('key', SmsTemplateKey::LEGACY_PRESENCE_NOTIFICATION)->where('enabled', true)->first();
            $iedTpl = SmsTemplate::query()->where('key', SmsTemplateKey::PRESENCE_IEDUCAR_SYNC)->where('enabled', true)->first();
            $rows[] = ['check' => 'Template SMS presence_catraca (ativo)', 'nível' => $catracaTpl ? 'ok' : 'aviso', 'estado' => $catracaTpl ? 'sim' : 'não', 'nota' => 'Configure em /integracoes/sms.'];
            $rows[] = ['check' => 'Template SMS presence_ieducar_sync (ativo)', 'nível' => $iedTpl ? 'ok' : 'aviso', 'estado' => $iedTpl ? 'sim' : 'não', 'nota' => 'Segundo SMS após HTTP OK ao iEducar.'];
        }

        $rows[] = ['check' => 'QUEUE_CONNECTION', 'nível' => 'info', 'estado' => (string) config('queue.default', 'sync'), 'nota' => 'sync executa jobs na mesma CLI; database exige worker ou o comando drena no fim.'];

        return $rows;
    }

    /**
     * @param  list<array{check: string, nível: string, estado: string, nota: string}>  $rows
     */
    private function printDiagnosticsTable(array $rows): void
    {
        $this->table(
            ['Verificação', 'Nível', 'Estado', 'Nota'],
            array_map(fn (array $r) => [$r['check'], $r['nível'], $r['estado'], mb_substr($r['nota'], 0, 64)], $rows),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function printJson(array $data): void
    {
        $enc = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->line($enc !== false ? $enc : '{}');
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(?Integration $ieducar, string $codAluno): array
    {
        $eventId = 'gide-pipeline-'.now()->format('YmdHisv');
        $creationDate = $this->resolveCreationDateForWindows($ieducar);

        return [
            'eventId' => $eventId,
            'creationDate' => $creationDate,
            'name' => $codAluno,
            'profile' => 'guest',
            'place' => 'Portaria (simulação pipeline)',
            'unity' => 'Aluno',
            'unityGroup' => 'Escola',
            'condominium' => 'GIDE CLI',
            'way' => 'Entrance',
            'accessMedia' => 'facial',
            'phone' => '5511999887766',
        ];
    }

    private function resolveCreationDateForWindows(?Integration $ieducar): string
    {
        $windows = $ieducar ? data_get($ieducar->extra, 'presence.windows', []) : [];
        if (! is_array($windows) || $windows === []) {
            return now()->toIso8601String();
        }
        $w = $windows[0];
        if (! is_array($w)) {
            return now()->toIso8601String();
        }
        $start = (string) ($w['start'] ?? '07:30');
        $parts = explode(':', $start);
        $h = (int) ($parts[0] ?? 7);
        $m = (int) ($parts[1] ?? 30);
        $tz = (string) config('app.timezone', 'UTC');
        $dt = now($tz)->startOfDay()->setTime(max(0, min(23, $h)), max(0, min(59, $m)), 0)->addMinutes(10);

        return $dt->toIso8601String();
    }
}
