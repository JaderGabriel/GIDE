<?php

use App\Jobs\SendIeducarFrequenciaRegistroJob;
use App\Models\GestorGuestLink;
use App\Models\IeducarFrequenciaRegistroDelivery;
use App\Models\Integration;
use App\Services\Gestor\GestorClient;
use App\Services\Ieducar\IeducarClient;
use App\Services\Integrations\DeliveryRetryDispatcher;
use App\Support\DateDisplay;
use App\Support\Ieducar\GideFrequenciaRegistroPlanB;
use App\Support\PostgresUsersIdSequence;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Validation\ValidationException;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('db:repair-users-id-sequence', function () {
    if (DB::getDriverName() !== 'pgsql') {
        $this->error('Este comando só se aplica a PostgreSQL (conexão atual: '.DB::getDriverName().').');

        return 1;
    }

    PostgresUsersIdSequence::sync();
    $this->info('Sequência de users.id sincronizada com o maior id existente (ou reiniciada se a tabela estiver vazia).');

    return 0;
})->purpose('Corrige sequência de users.id após import/restore (evita duplicate key em users_pkey)');

Artisan::command('gestor:import-postman {path : Caminho para a collection JSON v2.1}', function () {
    $path = (string) $this->argument('path');

    if (! is_file($path)) {
        $this->error("Arquivo não encontrado: {$path}");

        return 1;
    }

    $raw = file_get_contents($path);
    if ($raw === false || $raw === '') {
        $this->error('Não foi possível ler o arquivo.');

        return 1;
    }

    $collection = json_decode($raw, true);
    if (! is_array($collection)) {
        $this->error('JSON inválido.');

        return 1;
    }

    $endpoints = [];

    $walkItems = function (array $items) use (&$walkItems, &$endpoints) {
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            if (isset($item['item']) && is_array($item['item'])) {
                $walkItems($item['item']);

                continue;
            }

            $request = $item['request'] ?? null;
            if (! is_array($request)) {
                continue;
            }

            $method = strtoupper((string) ($request['method'] ?? ''));
            $url = $request['url'] ?? null;

            $rawUrl = null;
            if (is_string($url)) {
                $rawUrl = $url;
            } elseif (is_array($url)) {
                $rawUrl = $url['raw'] ?? null;
                if (! is_string($rawUrl) && isset($url['host'], $url['path']) && is_array($url['host']) && is_array($url['path'])) {
                    $rawUrl = implode('.', $url['host']).'/'.implode('/', $url['path']);
                }
            }

            if ($method !== '' && is_string($rawUrl) && $rawUrl !== '') {
                $endpoints[] = [
                    'name' => (string) ($item['name'] ?? ''),
                    'method' => $method,
                    'url' => $rawUrl,
                ];
            }
        }
    };

    $items = $collection['item'] ?? [];
    if (is_array($items)) {
        $walkItems($items);
    }

    $integration = Integration::query()->updateOrCreate(
        ['key' => 'gestor'],
        [
            'name' => 'Gestor de Dados da Catraca',
            'enabled' => true,
            'extra' => [
                'postman' => [
                    'info' => $collection['info'] ?? null,
                    'endpoints' => $endpoints,
                ],
            ],
        ],
    );

    $this->info('Importado com sucesso.');
    $this->line('Integração: '.$integration->id.' (key=gestor)');
    $this->line('Endpoints detectados: '.count($endpoints));

    return 0;
})->purpose('Importa collection do Postman do Gestor para o GIDE');

Artisan::command('ieducar:rotate-hmac', function () {
    $integration = Integration::query()->firstOrCreate(
        ['key' => 'ieducar'],
        ['name' => 'iEducar 2.11', 'enabled' => false, 'auth_type' => 'none'],
    );

    $integration->hmac_secret = base64_encode(random_bytes(32));
    $integration->save();

    $this->info('Segredo HMAC do iEducar gerado/rotacionado.');
    $this->line('Salvo em integrations.hmac_secret (key=ieducar).');
})->purpose('Gera/rotaciona o segredo HMAC usado pelo iEducar para enviar dados ao GIDE');

Artisan::command('ieducar:catraca-frequencia:confirmacao {cod_aluno? : Código do aluno (string)} {--idpes= : IDPES (string, obrigatório)} {--data= : ISO8601 da coleta (default=now)}', function () {
    $integration = Integration::query()->where('key', 'ieducar')->first();
    if (! $integration) {
        $this->error('Integração iEducar não encontrada (key=ieducar). Configure em /integracoes/ieducar.');

        return 1;
    }

    $codAluno = (string) ($this->argument('cod_aluno') ?? '');
    $idpes = (string) ($this->option('idpes') ?? '');
    if ($idpes === '') {
        $this->error('Informe --idpes (obrigatório).');

        return 1;
    }

    $dataColeta = (string) ($this->option('data') ?? '');
    if ($dataColeta === '') {
        $dataColeta = now()->toIso8601String();
    }

    $this->comment('data_coleta (ISO): '.$dataColeta);
    $this->comment('Interpretação local: '.DateDisplay::formatHuman(Carbon::parse($dataColeta), false));

    try {
        $resp = (new IeducarClient($integration))->postCatracaFrequenciaFacialConfirmacao([
            'identificacao' => [
                'cod_aluno' => $codAluno !== '' ? $codAluno : null,
                'idpes' => $idpes,
            ],
            'data_coleta' => $dataColeta,
        ]);
    } catch (Throwable $e) {
        $this->error('Erro: '.$e->getMessage());

        return 1;
    }

    $this->info('HTTP '.$resp->status());
    $this->line((string) $resp->body());

    return $resp->successful() ? 0 : 2;
})->purpose('Testa callback de confirmação facial (GIDE → iEducar)');

Artisan::command('ieducar:catraca-frequencia:aluno-consulta {cod_aluno? : Código do aluno (string)} {--idpes= : IDPES (string)}', function () {
    $integration = Integration::query()->where('key', 'ieducar')->first();
    if (! $integration) {
        $this->error('Integração iEducar não encontrada (key=ieducar). Configure em /integracoes/ieducar.');

        return 1;
    }

    $codAluno = (string) ($this->argument('cod_aluno') ?? '');
    $idpes = (string) ($this->option('idpes') ?? '');
    if ($codAluno === '' && $idpes === '') {
        $this->error('Informe cod_aluno (argumento) ou --idpes.');

        return 1;
    }

    try {
        $resp = (new IeducarClient($integration))->postCatracaFrequenciaAlunoConsulta([
            'identificacao' => [
                'cod_aluno' => $codAluno !== '' ? $codAluno : null,
                'idpes' => $idpes !== '' ? $idpes : null,
            ],
        ]);
    } catch (Throwable $e) {
        $this->error('Erro: '.$e->getMessage());

        return 1;
    }

    $this->info('HTTP '.$resp->status());
    $this->line((string) $resp->body());

    return $resp->successful() ? 0 : 2;
})->purpose('Testa consulta de aluno (GIDE → iEducar)');

Artisan::command(
    'ieducar:catraca-frequencia:frequencia-registro
    {--cod-aluno= : cod_aluno (obrigatório sem --json; formato B por aluno)}
    {--idpes= : idpes opcional (inteiro)}
    {--data= : data_ref base Y-m-d (default: hoje); na série o comando varia o dia}
    {--fonte=gide : gide ou outras (só 1 tentativa; na série alterna)}
    {--ausente : presente=false (só 1 tentativa; na série alterna padrões)}
    {--json= : Arquivo JSON (plano B); uma requisição; --tentativas>1 ignorado}
    {--tentativas=12 : Requisições HTTP em série (mínimo 1; default 12)}
    {--intervalo=0 : Segundos entre uma resposta e a próxima chamada}
    {--apply : meta.preview=false (gravação); senão preview}
    {--dry-run : Só imprime JSON de envio; sem HTTP}',
    function () {
        $integration = Integration::query()->where('key', 'ieducar')->first();
        if (! $integration) {
            $this->error('Integração iEducar não encontrada (key=ieducar). Configure em /integracoes/ieducar.');

            return 1;
        }

        $tentativas = (int) ($this->option('tentativas') ?? 12);
        if ($tentativas < 1) {
            $tentativas = 1;
        }
        if ($tentativas > 500) {
            $this->error('--tentativas aceita no máximo 500.');

            return 1;
        }

        $jsonPath = (string) ($this->option('json') ?? '');
        $payloads = [];

        if ($jsonPath !== '') {
            if ($tentativas > 1) {
                $this->warn('Com --json apenas uma requisição é enviada. --tentativas>1 ignorado.');
                $tentativas = 1;
            }
            if (! is_file($jsonPath)) {
                $this->error("Arquivo não encontrado: {$jsonPath}");

                return 1;
            }
            $raw = file_get_contents($jsonPath);
            if ($raw === false || $raw === '') {
                $this->error('Não foi possível ler o arquivo JSON.');

                return 1;
            }
            $decoded = json_decode($raw, true);
            if (! is_array($decoded)) {
                $this->error('JSON inválido (esperado objeto na raiz).');

                return 1;
            }
            try {
                $payloads[] = GideFrequenciaRegistroPlanB::validateAndNormalize($decoded);
            } catch (ValidationException $e) {
                foreach ($e->errors() as $msgs) {
                    foreach ($msgs as $m) {
                        $this->error($m);
                    }
                }

                return 1;
            }
        } else {
            $cod = (string) ($this->option('cod-aluno') ?? '');
            if ($cod === '' || ! ctype_digit($cod)) {
                $this->error('Informe --cod-aluno inteiro positivo ou use --json=/caminho/arquivo.json.');

                return 1;
            }
            $idpesOpt = (string) ($this->option('idpes') ?? '');
            if ($idpesOpt !== '' && ! ctype_digit($idpesOpt)) {
                $this->error('--idpes deve ser um inteiro positivo.');

                return 1;
            }

            $anchor = (string) ($this->option('data') ?? '');
            if ($anchor === '') {
                $anchor = now()->format('Y-m-d');
            }
            if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $anchor)) {
                $this->error('--data deve estar no formato Y-m-d.');

                return 1;
            }

            $fonteFixa = strtolower((string) ($this->option('fonte') ?? 'gide'));
            if (! in_array($fonteFixa, ['gide', 'outras'], true)) {
                $this->error('--fonte deve ser gide ou outras.');

                return 1;
            }

            $codAluno = (int) $cod;
            $anchorCarbon = Carbon::parse($anchor)->startOfDay();

            for ($i = 0; $i < $tentativas; $i++) {
                if ($tentativas === 1) {
                    $fonte = $fonteFixa;
                    $presente = ! (bool) $this->option('ausente');
                    $dataRef = $anchorCarbon->format('Y-m-d');
                } else {
                    $dayOffset = (int) floor($i * 1.7) % 21;
                    $dataRef = $anchorCarbon->copy()->subDays($dayOffset)->format('Y-m-d');
                    $fonte = ($i % 3 === 0) ? 'outras' : 'gide';
                    $presente = match ($i % 5) {
                        0, 1, 4 => true,
                        default => false,
                    };
                }

                $ident = ['cod_aluno' => $codAluno];
                if ($idpesOpt !== '') {
                    $ident['idpes'] = (int) $idpesOpt;
                }

                $row = [
                    'meta' => [
                        'contract_version' => IeducarClient::CAT_FREQUENCIA_CONTRACT_VERSION,
                    ],
                    'fonte' => $fonte,
                    'presente' => $presente,
                    'identificacao' => $ident,
                    'data_ref' => $dataRef,
                ];

                try {
                    $payloads[] = GideFrequenciaRegistroPlanB::validateAndNormalize($row);
                } catch (ValidationException $e) {
                    foreach ($e->errors() as $msgs) {
                        foreach ($msgs as $m) {
                            $this->error($m);
                        }
                    }

                    return 1;
                }
            }
        }

        $apply = (bool) $this->option('apply');
        $dryRun = (bool) $this->option('dry-run');
        $intervalo = (float) ($this->option('intervalo') ?? 0);
        if ($intervalo < 0) {
            $intervalo = 0;
        }

        foreach ($payloads as $idx => &$payload) {
            $meta = (array) ($payload['meta'] ?? []);
            $meta['contract_version'] = IeducarClient::CAT_FREQUENCIA_CONTRACT_VERSION;
            $meta['preview'] = $apply ? false : true;
            $payload['meta'] = $meta;
        }
        unset($payload);

        $this->comment('Modo: '.($apply ? 'gravação (persiste no i-Educar)' : 'preview (sem gravar no i-Educar)'));
        $this->comment('Requisições planejadas: '.count($payloads).($dryRun ? ' (dry-run)' : '').($intervalo > 0 ? sprintf(' · intervalo %.3fs entre chamadas', $intervalo) : ''));

        if ($dryRun) {
            foreach ($payloads as $i => $payload) {
                $this->newLine();
                $this->warn('── Tentativa '.($i + 1).'/'.count($payloads).' (dry-run) · envio em '.now()->toIso8601String());
                $enc = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                $this->line($enc !== false ? $enc : '{}');
            }
            $this->info('Dry-run: nenhuma requisição HTTP nem registro na fila.');

            return 0;
        }

        if (! $integration->enabled) {
            $this->error('Integração iEducar desabilitada. Habilite em /integracoes/ieducar (o job exige integração ativa).');

            return 1;
        }

        $anyFailed = false;

        foreach ($payloads as $i => $payload) {
            $this->newLine();
            $this->warn('═══ Tentativa '.($i + 1).'/'.count($payloads).' · disparo em '.now()->toIso8601String().' ═══');
            $reqJson = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            $this->comment('→ Corpo persistido (request):');
            $this->line($reqJson !== false ? $reqJson : '{}');

            $delivery = IeducarFrequenciaRegistroDelivery::query()->create([
                'user_id' => null,
                'mode' => $apply ? IeducarFrequenciaRegistroDelivery::MODE_APPLY : IeducarFrequenciaRegistroDelivery::MODE_PREVIEW,
                'status' => IeducarFrequenciaRegistroDelivery::STATUS_PENDING,
                'payload' => $payload,
            ]);

            $trackUrl = route('admin.ieducar-frequencia-deliveries.show', ['id' => $delivery->id], true);
            $this->comment('Rastreamento: entrega #'.$delivery->id.' · '.$trackUrl);

            SendIeducarFrequenciaRegistroJob::dispatchSync($delivery->id);

            $delivery->refresh();

            $http = $delivery->http_status;
            $this->info('← HTTP '.($http !== null ? (string) $http : '—').' · status entrega: '.$delivery->status);

            $this->comment('← Corpo (response gravado na entrega):');
            if (is_array($delivery->response_json)) {
                $pretty = json_encode($delivery->response_json, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                $this->line($pretty !== false ? $pretty : '{}');
            } elseif ($delivery->error_message) {
                $this->line($delivery->error_message);
            } else {
                $this->line('(sem corpo de resposta)');
            }

            if ($delivery->status === IeducarFrequenciaRegistroDelivery::STATUS_FAILED
                || ! is_numeric($http)
                || $http < 200
                || $http >= 300) {
                $anyFailed = true;
            }

            if ($i < count($payloads) - 1 && $intervalo > 0) {
                usleep((int) round($intervalo * 1_000_000));
            }
        }

        return $anyFailed ? 2 : 0;
    }
)->purpose('Registros de frequência (GIDE → iEducar): grava em fila/entregas, executa job síncrono e URL de monitoramento; preview na API iEducar');

Artisan::command('gestor:auth:test-config', function () {
    $integration = Integration::query()->where('key', 'gestor')->first();
    if (! $integration) {
        $this->error('Integração Gestor não encontrada (key=gestor). Configure em /integracoes/gestor.');

        return 1;
    }

    try {
        $token = (new GestorClient($integration))->signIn();
    } catch (Throwable $e) {
        $this->error('Falha no auth do Gestor (config do banco): '.$e->getMessage());

        return 2;
    }

    $this->info('Auth do Gestor OK (config do banco).');
    $this->line('Token (início): '.mb_substr($token, 0, 16).'...');

    return 0;
})->purpose('Testa Signin do Gestor usando a integração do banco (key=gestor)');

Artisan::command('gestor:auth:test-setup {--base= : Base URL HTTPS do Gestor (sem credenciais fixas no código)} {--path= : Path do Signin, default=/SDK/Auth/Signin} {--appkey= : ApplicationKey} {--username= : Username} {--password= : Password}', function () {
    $base = rtrim((string) ($this->option('base') ?? ''), '/');
    $path = (string) ($this->option('path') ?? '/SDK/Auth/Signin');
    $appKey = (string) ($this->option('appkey') ?? '');
    $username = (string) ($this->option('username') ?? '');
    $password = (string) ($this->option('password') ?? '');

    if ($base === '' || $appKey === '' || $username === '' || $password === '') {
        $this->error('Informe --base, --appkey, --username e --password.');

        return 1;
    }

    $url = $base.'/'.ltrim($path, '/');

    try {
        $resp = Http::timeout(30)
            ->withHeaders([
                'ApplicationKey' => $appKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->post($url, [
                'username' => $username,
                'password' => $password,
            ]);
    } catch (Throwable $e) {
        $this->error('Erro ao chamar Signin: '.$e->getMessage());

        return 2;
    }

    $this->info('HTTP '.$resp->status());
    $body = (string) $resp->body();
    $this->line(mb_substr($body, 0, 2000));

    if (! $resp->successful()) {
        return 3;
    }

    $token = (string) ($resp->json('token') ?? $resp->json('access_token') ?? '');
    if ($token !== '') {
        $this->info('Token (início): '.mb_substr($token, 0, 16).'...');
    }

    return 0;
})->purpose('Testa Signin do Gestor com parâmetros (base/appkey/credenciais) sem depender do banco');

Artisan::command('gestor:invite:get {inviteId : ID do Invite no Gestor}', function () {
    $integration = Integration::query()->where('key', 'gestor')->first();
    if (! $integration) {
        $this->error('Integração Gestor não encontrada (key=gestor). Configure em /integracoes/gestor.');

        return 1;
    }

    $inviteId = (string) ($this->argument('inviteId') ?? '');
    if ($inviteId === '') {
        $this->error('Informe inviteId.');

        return 1;
    }

    try {
        $client = new GestorClient($integration);
        // Endpoint conforme doc: GET /SDK/Invite/{InviteId}
        $resp = $client->request('get', '/SDK/Invite/'.urlencode($inviteId));
    } catch (Throwable $e) {
        $this->error('Erro: '.$e->getMessage());

        return 2;
    }

    $this->info('HTTP '.$resp->status());
    $body = (string) $resp->body();
    if ($resp->status() === 204) {
        $this->warn('Resposta 204 (No Content). Em geral indica que o InviteId não existe no seu escopo ou não há conteúdo para retornar.');
    }
    $this->line(mb_substr($body, 0, 20000));

    return $resp->successful() ? 0 : 3;
})->purpose('Consulta um Invite por ID no Gestor (usa integração do banco)');

Artisan::command('gestor:invite:list {--timeout=15 : Timeout em segundos} {--limit=50 : Limite de itens (quando suportado)}', function () {
    $integration = Integration::query()->where('key', 'gestor')->first();
    if (! $integration) {
        $this->error('Integração Gestor não encontrada (key=gestor). Configure em /integracoes/gestor.');

        return 1;
    }

    $timeout = (int) ($this->option('timeout') ?? 15);
    if ($timeout <= 0) {
        $timeout = 15;
    }
    if ($timeout > 60) {
        $timeout = 60;
    }

    $limit = (int) ($this->option('limit') ?? 50);
    if ($limit <= 0) {
        $limit = 50;
    }
    if ($limit > 500) {
        $limit = 500;
    }

    try {
        $client = new GestorClient($integration);
        // Endpoint conforme doc: lista Invites disponíveis para o integrador.
        // Alguns SDKs aceitam paginação/limit via query; mandamos como tentativa (se ignorar, ok).
        $resp = $client->request('get', '/SDK/Invite?limit='.urlencode((string) $limit));
        if ($resp->status() === 404) {
            // fallback comum
            $resp = $client->request('get', '/SDK/Invites?limit='.urlencode((string) $limit));
        }
    } catch (Throwable $e) {
        $this->error('Erro: '.$e->getMessage());

        return 2;
    }

    $this->info('HTTP '.$resp->status());
    $this->line(mb_substr((string) $resp->body(), 0, 20000));

    return $resp->successful() ? 0 : 3;
})->purpose('Lista Invites disponíveis no Gestor (usa integração do banco)');

Artisan::command('gestor:invite:create-simulate {--cod_aluno= : Cod aluno (iEducar) usado no name} {--cod_matricula= : Código da matrícula (iEducar) usado no id do Invite} {--ano= : Ano letivo (default=ano atual)} {--unityId= : UnityId (sobrescreve extra/config)} {--accessProfileId= : AccessProfileId (sobrescreve extra/config)} {--path= : Path do create Invite (sobrescreve extra/config)}', function () {
    $integration = Integration::query()->where('key', 'gestor')->first();
    if (! $integration) {
        $this->error('Integração Gestor não encontrada (key=gestor). Configure em /integracoes/gestor.');

        return 1;
    }

    $codAluno = (string) ($this->option('cod_aluno') ?? '');
    if ($codAluno === '') {
        $this->error('Informe --cod_aluno (ex.: 211).');

        return 1;
    }

    $ano = (int) ($this->option('ano') ?? date('Y'));
    if ($ano < 2000 || $ano > 2100) {
        $ano = (int) date('Y');
    }

    $unityId = (int) ($this->option('unityId') ?: data_get($integration->extra, 'onboarding.unity_id') ?: data_get($integration->extra, 'defaults.unity_id') ?: config('integrations.gestor.default_unity_id'));
    $accessProfileId = (int) ($this->option('accessProfileId') ?: data_get($integration->extra, 'onboarding.access_profile_id') ?: data_get($integration->extra, 'defaults.access_profile_id') ?: config('integrations.gestor.default_access_profile_id'));
    if ($unityId <= 0) {
        $this->error('unityId ausente: configure em /integracoes/gestor ou GESTOR_DEFAULT_UNITY_ID, ou use --unityId.');

        return 1;
    }
    if ($accessProfileId <= 0) {
        $this->error('accessProfileId ausente: configure em /integracoes/gestor ou GESTOR_DEFAULT_ACCESS_PROFILE_ID, ou use --accessProfileId.');

        return 1;
    }

    $path = (string) ($this->option('path') ?? '');
    if ($path === '') {
        $path = (string) (data_get($integration->extra, 'endpoints.enrollment_sync_path') ?: config('integrations.gestor.default_enrollment_sync_path') ?: '');
    }
    if ($path === '') {
        $this->error('Path do Invite ausente: defina endpoints.enrollment_sync_path na integração ou GESTOR_DEFAULT_ENROLLMENT_SYNC_PATH no .env, ou use --path.');

        return 1;
    }

    // Payload “simulado” (ieducar snapshot) só para depuração/consistência.
    $ieducarSim = [
        'meta' => ['contract_version' => '1.0', 'operation' => 'nova', 'emitted_at' => now()->toIso8601String()],
        'identificacao' => ['cod_aluno' => $codAluno, 'idpes' => null],
        'matricula' => ['ano_letivo' => $ano],
    ];

    $invitePayload = [
        'id' => null,
        'unityId' => $unityId,
        'name' => $codAluno,
        'start' => sprintf('%04d-01-01T01:00:00Z', $ano),
        'end' => CarbonImmutable::parse(sprintf('%04d-01-01T01:00:00Z', $ano))->addDays(365)->toIso8601ZuluString(),
        'accessProfileId' => $accessProfileId,
        'guests' => [
            ['id' => null, 'name' => $codAluno],
        ],
    ];

    $client = new GestorClient($integration);

    // Mostra URL efetiva usada (inclui normalização do baseUrl no client).
    $base = (new ReflectionClass($client))->getMethod('baseUrl');
    $base->setAccessible(true);
    $baseUrl = (string) $base->invoke($client);
    $url = rtrim($baseUrl, '/').'/'.ltrim($path, '/');

    $this->info('Invite Create (simulação)');
    $this->line('URL: '.$url);
    $this->line('Payload (Invite): '.json_encode($invitePayload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    $this->line('Snapshot iEducar (sim): '.json_encode($ieducarSim, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

    try {
        $resp = $client->request('post', $path, $invitePayload);
    } catch (Throwable $e) {
        $this->error('Erro ao criar Invite: '.$e->getMessage());

        return 2;
    }

    $this->info('HTTP '.$resp->status());
    $body = (string) $resp->body();
    $this->line(mb_substr($body, 0, 20000));

    $json = $resp->json();
    $inviteId = null;
    $guestId = null;
    if (is_array($json)) {
        $inviteId = $json['id'] ?? $json['Id'] ?? data_get($json, 'invite.id') ?? data_get($json, 'Invite.Id');
        $guestId = data_get($json, 'guests.0.id')
            ?? data_get($json, 'guests.0.Id')
            ?? data_get($json, 'Guests.0.id')
            ?? data_get($json, 'Guests.0.Id')
            ?? data_get($json, 'guestId')
            ?? data_get($json, 'GuestId')
            ?? data_get($json, 'guest.id')
            ?? data_get($json, 'Guest.Id');
    }

    // fallback: tenta listagem para encontrar guest com name=cod_aluno
    if (! is_numeric($guestId)) {
        try {
            $list = $client->listInvites(300);
            $lj = $list->json();
            if (is_array($lj)) {
                $arr = array_is_list($lj) ? $lj : (data_get($lj, 'data') ?? data_get($lj, 'items') ?? $lj);
                if (is_array($arr)) {
                    foreach ($arr as $inv) {
                        if (! is_array($inv)) {
                            continue;
                        }
                        $guests = data_get($inv, 'guests') ?? data_get($inv, 'Guests');
                        if (! is_array($guests)) {
                            continue;
                        }
                        foreach ($guests as $g) {
                            if (! is_array($g)) {
                                continue;
                            }
                            $gName = (string) (data_get($g, 'name') ?? data_get($g, 'Name') ?? '');
                            if ($gName === $codAluno) {
                                $guestId = data_get($g, 'id') ?? data_get($g, 'Id') ?? null;
                                $inviteId = $inviteId ?? (data_get($inv, 'id') ?? data_get($inv, 'Id') ?? null);
                                break 2;
                            }
                        }
                    }
                }
            }
        } catch (Throwable) {
        }
    }

    if (is_numeric($guestId)) {
        $this->info('guest_id='.$guestId);
        if (is_numeric($inviteId)) {
            $this->line('invite_id='.$inviteId);
        }

        try {
            $link = GestorGuestLink::query()->firstOrCreate(['cod_aluno' => $codAluno], ['cod_aluno' => $codAluno]);
            $link->invite_id = is_numeric($inviteId) ? (int) $inviteId : $link->invite_id;
            $link->guest_id = (int) $guestId;
            $link->last_invite_http_status = (int) $resp->status();
            $link->last_invite_response_json = is_array($json) ? $json : null;
            $link->last_error = null;
            $link->save();
        } catch (Throwable) {
        }

        return 0;
    }

    $this->warn('guest_id não encontrado na resposta nem na listagem. Veja o JSON/body acima para ajustar o parser.');

    return $resp->successful() ? 0 : 3;
})->purpose('Simula Invite Create no Gestor e retorna guest_id + URL/payload/JSON para depuração');

Artisan::command('integrations:encrypt-existing {--dry-run : Apenas mostra o que seria alterado}', function () {
    $dryRun = (bool) $this->option('dry-run');

    $rows = DB::table('integrations')
        ->select(['id', 'key', 'auth_token', 'hmac_secret', 'extra'])
        ->orderBy('id')
        ->get();

    $changed = 0;
    $changedAuth = 0;
    $changedHmac = 0;
    $changedExtra = 0;

    foreach ($rows as $row) {
        $updates = [];

        // auth_token
        if ($row->auth_token !== null && $row->auth_token !== '') {
            $alreadyEncrypted = true;
            try {
                Crypt::decryptString((string) $row->auth_token);
            } catch (Throwable) {
                $alreadyEncrypted = false;
            }

            if (! $alreadyEncrypted) {
                $updates['auth_token'] = Crypt::encryptString((string) $row->auth_token);
                $changedAuth++;
            }
        }

        // hmac_secret
        if ($row->hmac_secret !== null && $row->hmac_secret !== '') {
            $alreadyEncrypted = true;
            try {
                Crypt::decryptString((string) $row->hmac_secret);
            } catch (Throwable) {
                $alreadyEncrypted = false;
            }

            if (! $alreadyEncrypted) {
                $updates['hmac_secret'] = Crypt::encryptString((string) $row->hmac_secret);
                $changedHmac++;
            }
        }

        // extra (encrypted:array) — armazenamos como JSON criptografado
        if ($row->extra !== null && $row->extra !== '') {
            $alreadyEncrypted = true;
            try {
                Crypt::decryptString((string) $row->extra);
            } catch (Throwable) {
                $alreadyEncrypted = false;
            }

            if (! $alreadyEncrypted) {
                $arr = null;
                if (is_array($row->extra)) {
                    $arr = $row->extra;
                } elseif (is_string($row->extra)) {
                    $decoded = json_decode($row->extra, true);
                    $arr = is_array($decoded) ? $decoded : ['__raw' => (string) $row->extra];
                } else {
                    // stdClass / outros
                    $arr = json_decode(json_encode($row->extra), true);
                    if (! is_array($arr)) {
                        $arr = ['__raw' => (string) json_encode($row->extra)];
                    }
                }

                $updates['extra'] = Crypt::encryptString(json_encode(
                    $arr,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ));
                $changedExtra++;
            }
        }

        if (count($updates) > 0) {
            $changed++;
            $this->line(sprintf(
                '%s %s: %s',
                $dryRun ? '[dry-run]' : '[update]',
                (string) $row->key,
                implode(', ', array_keys($updates)),
            ));

            if (! $dryRun) {
                DB::table('integrations')->where('id', $row->id)->update($updates);
            }
        }
    }

    $this->info('Finalizado.');
    $this->line('Linhas alteradas: '.$changed);
    $this->line('auth_token criptografado: '.$changedAuth);
    $this->line('hmac_secret criptografado: '.$changedHmac);
    $this->line('extra criptografado: '.$changedExtra);

    if ($dryRun) {
        $this->comment('Nenhuma alteração foi aplicada (dry-run). Rode sem --dry-run para aplicar.');
    }

    return 0;
})->purpose('Criptografa valores existentes em integrations (auth_token, hmac_secret, extra)');

Artisan::command('gide:deliveries:retry-due {--recover-stale : Re-despacha também entregas com attempts=0 antigas (ex.: job nunca consumido)}', function () {
    $dispatcher = new DeliveryRetryDispatcher;
    $recover = (bool) $this->option('recover-stale');
    $r = $dispatcher->dispatchAll($recover);
    $this->info('Re-despachos: outbound='.$r['outbound'].', sms='.$r['sms'].($recover ? ' (inclui stale)' : ''));
    $this->comment(DateDisplay::cliReferenceLine());

    return 0;
})->purpose('Re-enfileira matrícula→Gestor e SMS com next_retry_at vencido (complementa a fila)');

Artisan::command('gide:queue:work-once', function () {
    $connection = (string) config('queue.default', 'database');
    if ($connection === 'sync') {
        $this->comment('QUEUE_CONNECTION=sync: fila inline; nada a drenar.');

        return 0;
    }

    $code = Artisan::call('queue:work', [
        $connection,
        '--once' => true,
        '--no-ansi' => true,
    ]);
    $this->line(trim((string) Artisan::output()));
    if ($code !== 0) {
        $this->warn('queue:work retornou código '.$code);
    }
    $this->comment(DateDisplay::cliReferenceLine());

    return 0;
})->purpose('Processa um job da fila (útil em cron sem daemon queue:work)');

Schedule::command('gide:deliveries:retry-due')->everyMinute()->withoutOverlapping(120);
Schedule::command('gide:queue:work-once')->everyMinute()->withoutOverlapping(120);
