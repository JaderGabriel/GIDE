<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Integration;
use App\Models\OutboundDelivery;
use App\Models\SmsDelivery;
use App\Services\Gestor\GestorClient;
use App\Services\Ieducar\IeducarClient;
use App\Support\DateDisplay;
use App\Support\OutboundDeliveryStatuses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class IntegrationOverviewController extends Controller
{
    public function index(Request $request)
    {
        // “Esperadas” no GIDE hoje (podem não existir no banco ainda).
        $expected = [
            'ieducar' => 'iEducar 2.11',
            'catraca_frequencia' => 'Catraca/Frequência (iEducar → GIDE)',
            'gestor' => 'Gestor (Porter/Kiper SDK)',
            'sms' => 'SMS',
        ];

        $items = Integration::query()->orderBy('key')->get()->keyBy('key');
        foreach ($expected as $key => $name) {
            if (! $items->has($key)) {
                $items->put($key, new Integration([
                    'key' => $key,
                    'name' => $name,
                    'enabled' => false,
                    'auth_type' => 'none',
                ]));
            }
        }

        $items = $items->sortBy(fn ($i) => (string) $i->key)->values();
        $order = ['ieducar', 'catraca_frequencia', 'gestor', 'sms'];
        $byKey = $items->keyBy(fn ($i) => (string) ($i->key ?? ''));
        $items = collect($order)
            ->map(fn (string $k) => $byKey->get($k))
            ->filter()
            ->merge($byKey->except($order)->values())
            ->values();

        $sms = $byKey->get('sms');
        $gestor = $byKey->get('gestor');
        $integrationConfigured = function (?Integration $i): bool {
            if (! $i) {
                return false;
            }
            $hasBase = is_string($i->base_url ?? null) && (string) $i->base_url !== '';
            $hasAuthToken = is_string($i->auth_token ?? null) && (string) $i->auth_token !== '';
            $hasHmac = is_string($i->hmac_secret ?? null) && (string) $i->hmac_secret !== '';

            return $hasBase || $hasAuthToken || $hasHmac || ! empty($i->extra);
        };
        $smsConfigured = $integrationConfigured($sms);
        $gestorConfigured = $integrationConfigured($gestor);
        $gestorEnabled = (bool) ($gestor?->enabled ?? false);
        $smsEnabled = (bool) ($sms?->enabled ?? false);
        $smsChainReady = $smsConfigured && $gestorConfigured && $gestorEnabled && $smsEnabled;

        $metrics = [
            'total' => $items->count(),
            'enabled' => $items->filter(fn ($i) => (bool) ($i->enabled ?? false))->count(),
            'configured' => $items->filter(function ($i) {
                $hasBase = is_string($i->base_url ?? null) && (string) $i->base_url !== '';
                $hasAuthToken = is_string($i->auth_token ?? null) && (string) $i->auth_token !== '';
                $hasHmac = is_string($i->hmac_secret ?? null) && (string) $i->hmac_secret !== '';

                return $hasBase || $hasAuthToken || $hasHmac || ! empty($i->extra);
            })->count(),
        ];
        $metrics['not_configured'] = max(0, $metrics['total'] - $metrics['configured']);
        $metrics['disabled'] = max(0, $metrics['total'] - $metrics['enabled']);

        $dbMetrics = [];
        try {
            $maxAttempts = max(1, (int) config('gide.deliveries.max_attempts', 3));

            $smsRetryDue = (int) SmsDelivery::query()
                ->whereNull('sent_at')
                ->where('attempts', '<', $maxAttempts)
                ->whereNotNull('next_retry_at')
                ->where('next_retry_at', '<=', now())
                ->count();

            $dbMetrics = [
                'gide_facial_inbounds' => (int) DB::table('gide_facial_inbounds')->count(),
                'facial_send_requests' => (int) DB::table('facial_send_requests')->count(),
                'facial_enroll_attempts' => (int) DB::table('facial_enroll_attempts')->count(),
                'facial_status_snapshots' => (int) DB::table('facial_ieducar_status_snapshots')->count(),
                'outbound_deliveries' => (int) DB::table('outbound_deliveries')->count(),
                'outbound_pending' => (int) OutboundDelivery::query()->whereNull('delivered_at')->count(),
                'outbound_failed' => (int) OutboundDelivery::query()->where('delivery_status', OutboundDeliveryStatuses::FAILED)->count(),
                'outbound_retry_due' => (int) OutboundDelivery::query()->eligibleForRetryDispatch()->count(),
                'sms_deliveries' => (int) DB::table('sms_deliveries')->count(),
                'sms_pending' => (int) SmsDelivery::query()->whereNull('sent_at')->where('attempts', '<', $maxAttempts)->count(),
                'sms_retry_due' => $smsRetryDue,
                'jobs_pending' => (int) DB::table('jobs')->count(),
                'gestor_guest_links' => (int) DB::table('gestor_guest_links')->count(),
            ];
        } catch (\Throwable $e) {
            $dbMetrics = ['error' => $e->getMessage()];
        }

        $queueSnapshot = $this->buildQueueSnapshot();

        $integrationCards = collect(['ieducar', 'gestor', 'sms'])
            ->map(fn (string $k) => $byKey->get($k))
            ->filter()
            ->values();

        return view('integrations.overview', [
            'items' => $items,
            'integrationCards' => $integrationCards,
            'catracaFrequencia' => $byKey->get('catraca_frequencia'),
            'lastTest' => session('overview_last_test'),
            'lastTestKey' => session('overview_last_test_key'),
            'metrics' => $metrics,
            'dbMetrics' => $dbMetrics,
            'queueSnapshot' => $queueSnapshot,
            'smsChainReady' => $smsChainReady,
            'smsConfigured' => $smsConfigured,
            'gestorConfigured' => $gestorConfigured,
            'gestorEnabled' => $gestorEnabled,
            'smsEnabled' => $smsEnabled,
        ]);
    }

    /**
     * Teste ida/volta GIDE ↔ iEducar (HTTP host + API catraca-frequência com Bearer).
     */
    public function bridgeProbeIeducar(Request $request): JsonResponse
    {
        $timeout = $this->clampTimeout((int) $request->input('timeout', 12));
        $integration = Integration::query()->where('key', 'ieducar')->first();
        if (! $integration) {
            return $this->bridgeErrorJson('Integração ieducar não existe no banco.');
        }

        $steps = [];
        $okAll = true;

        try {
            $base = rtrim((string) ($integration->base_url ?? ''), '/');
            if ($base === '') {
                throw new \RuntimeException('base_url vazia.');
            }
            $r0 = Http::timeout($timeout)->get($base);
            $host = (string) (parse_url($base, PHP_URL_HOST) ?? $base);
            $steps[] = [
                'name' => '1. Conectividade (rede) — GET na raiz da base_url do iEducar',
                'direction' => 'gide_saida_http_get_host_ieducar',
                'detail' => 'Só verifica TLS/DNS e resposta HTTP do host configurado em integrations(key=ieducar).base_url. Não chama rotas do contrato catraca-frequência.',
                'ok' => $r0->status() < 500,
                'message' => 'HTTP '.$r0->status().' · host: '.$host,
            ];
            if ($r0->status() >= 500) {
                $okAll = false;
            }

            $client = new IeducarClient($integration);
            // cod_aluno deve ser inteiro no validador do iEducar; valor improvável só para sonda de rota/auth.
            $r1 = $client->postCatracaFrequenciaAlunoConsulta([
                'identificacao' => [
                    'cod_aluno' => 999_999_001,
                    'idpes' => null,
                ],
            ]);
            $bodyRaw = (string) $r1->body();
            $bodyPretty = $this->shortenJsonBodyPretty($bodyRaw, 1400);
            $steps[] = [
                'name' => '2. API Catraca-Frequência — POST aluno/consulta (GIDE → iEducar → JSON de resposta)',
                'direction' => 'gide_saida_post_bearer_ieducar_entrada_validacao_negocio',
                'detail' => 'Usa o token Bearer (confirmacao_token ou auth_token) em Authorization. Caminho: '.IeducarClient::CAT_FREQUENCIA_ALUNO_CONSULTA_PATH.'. Respostas 4xx de validação contam como rota viva; 5xx contam como falha.',
                'ok' => $r1->status() < 500,
                'message' => 'HTTP '.$r1->status().($bodyPretty !== '' ? ' · corpo: '.$bodyPretty : ''),
            ];
            if ($r1->status() >= 500) {
                $okAll = false;
            }
        } catch (\Throwable $e) {
            $steps[] = [
                'name' => 'Falha inesperada no teste da ponte iEducar',
                'direction' => 'erro_cliente_http_ou_config',
                'detail' => 'Verifique base_url, token Bearer e reachability.',
                'ok' => false,
                'message' => $e->getMessage(),
            ];
            $okAll = false;
        }

        $steps[] = [
            'name' => '3. Fluxo inverso (referência, não disparado por este botão) — iEducar → GIDE',
            'direction' => 'ieducar_saida_hmac_para_gide_inbound',
            'detail' => 'Matrícula/facial/frequência chegam com HMAC (X-Signature + segredo em integrations). É independente do Bearer usado no passo 2.',
            'ok' => true,
            'message' => 'Valide com o cliente iEducar, Postman ou os endpoints documentados; este painel só testa saída GIDE→iEducar.',
        ];

        return $this->bridgeJsonResponse($okAll, $steps);
    }

    /**
     * Teste ida/volta GIDE ↔ Gestor (Signin + leitura SDK).
     */
    public function bridgeProbeGestor(Request $request): JsonResponse
    {
        $timeout = $this->clampTimeout((int) $request->input('timeout', 12));
        $integration = Integration::query()->where('key', 'gestor')->first();
        if (! $integration) {
            return $this->bridgeErrorJson('Integração gestor não existe no banco.');
        }

        $steps = [];
        $okAll = true;

        try {
            $client = new GestorClient($integration);
            $token = $client->signIn();
            $steps[] = [
                'name' => '1. Autenticação no SDK — POST Signin (GIDE → Gestor / Kiper)',
                'direction' => 'gide_saida_post_signin_applicationkey_recebe_bearer',
                'detail' => 'Usa integrations(key=gestor).base_url, ApplicationKey e credenciais em extra.auth. O token Bearer é usado nas chamadas seguintes.',
                'ok' => true,
                'message' => 'Bearer obtido (prefixo): '.mb_substr($token, 0, 14).'…',
            ];

            $list = $client->listInvites(min(10, 200));
            $snippet = $this->shortenJsonBodyPretty((string) $list->body(), 900);
            $steps[] = [
                'name' => '2. Chamada autenticada — GET listagem de convites (GIDE → Gestor → JSON na resposta)',
                'direction' => 'gide_saida_get_bearer_gestor_resposta_http',
                'detail' => 'Confirma que o token do passo 1 vale para leitura no SDK (rotas /SDK/Invite ou fallback documentado no client).',
                'ok' => $list->status() < 500,
                'message' => 'HTTP '.$list->status().($snippet !== '' ? ' · corpo: '.preg_replace('/\s+/', ' ', $snippet) : ''),
            ];
            if ($list->status() >= 500) {
                $okAll = false;
            }
        } catch (\Throwable $e) {
            $steps[] = [
                'name' => 'Falha no teste da ponte Gestor',
                'direction' => 'erro_signin_ou_listagem',
                'detail' => 'Revise base_url, ApplicationKey, usuário e senha em /integracoes/gestor.',
                'ok' => false,
                'message' => $e->getMessage(),
            ];
            $okAll = false;
        }

        $steps[] = [
            'name' => '3. Fluxo inverso (referência) — Gestor → GIDE',
            'direction' => 'gestor_saida_webhook_para_gide_hmac',
            'detail' => 'Eventos de catraca e presença chegam em rotas API do GIDE assinadas com HMAC (integração gestor). Não usa o Bearer do passo 1.',
            'ok' => true,
            'message' => 'Valide com o ambiente Gestor ou documentação de webhook.',
        ];

        return $this->bridgeJsonResponse($okAll, $steps);
    }

    /**
     * Teste ida/volta GIDE ↔ API SMS (autenticado).
     */
    public function bridgeProbeSms(Request $request): JsonResponse
    {
        $timeout = $this->clampTimeout((int) $request->input('timeout', 12));
        $integration = Integration::query()->where('key', 'sms')->first();
        if (! $integration) {
            return $this->bridgeErrorJson('Integração sms não existe no banco.');
        }

        $steps = [];
        $okAll = true;

        try {
            $base = rtrim((string) ($integration->base_url ?? ''), '/');
            $apiToken = (string) ($integration->auth_token ?? '');
            if ($base === '') {
                throw new \RuntimeException('base_url vazia.');
            }
            if ($apiToken === '') {
                throw new \RuntimeException('Token API vazio.');
            }

            $path = '/channels/sms/messages?limit=1';
            $resp = Http::timeout($timeout)
                ->withHeaders([
                    'X-API-TOKEN' => $apiToken,
                    'Accept' => 'application/json',
                ])
                ->get($base.$path);

            $snippet = $this->shortenJsonBodyPretty((string) $resp->body(), 900);
            $steps[] = [
                'name' => '1. SMS — GET listagem de mensagens (GIDE → provedor → JSON na resposta)',
                'direction' => 'gide_saida_get_x_api_token_provedor_sms_resposta',
                'detail' => 'Usa integrations(key=sms).base_url e header X-API-TOKEN. Caminho de teste: '.$path.'.',
                'ok' => $resp->status() < 500,
                'message' => 'HTTP '.$resp->status().($snippet !== '' ? ' · corpo: '.preg_replace('/\s+/', ' ', $snippet) : ''),
            ];
            if ($resp->status() >= 500) {
                $okAll = false;
            }
        } catch (\Throwable $e) {
            $steps[] = [
                'name' => 'Falha no teste da ponte SMS',
                'direction' => 'erro_http_ou_config_sms',
                'detail' => 'Revise base_url e token em /integracoes/sms.',
                'ok' => false,
                'message' => $e->getMessage(),
            ];
            $okAll = false;
        }

        return $this->bridgeJsonResponse($okAll, $steps);
    }

    private function bridgeJsonResponse(bool $ok, array $steps): JsonResponse
    {
        $now = now();

        return response()->json([
            'ok' => $ok,
            'steps' => $steps,
            'tested_at' => $now->toIso8601String(),
            'tested_at_display' => DateDisplay::formatHuman($now, true),
        ]);
    }

    private function bridgeErrorJson(string $message): JsonResponse
    {
        $now = now();

        return response()->json([
            'ok' => false,
            'steps' => [
                [
                    'name' => 'Configuração ausente ou integração não persistida',
                    'direction' => 'gide_config_integracao_ausente',
                    'detail' => 'Crie/edite a integração na tela /integracoes antes de testar a ponte.',
                    'ok' => false,
                    'message' => $message,
                ],
            ],
            'tested_at' => $now->toIso8601String(),
            'tested_at_display' => DateDisplay::formatHuman($now, true),
        ], 422);
    }

    private function clampTimeout(int $t): int
    {
        if ($t <= 0) {
            $t = 10;
        }
        if ($t > 60) {
            $t = 60;
        }

        return $t;
    }

    /**
     * @return array{jobs: array<int, array<string, mixed>>, outbound: array<int, array<string, mixed>>, sms: array<int, array<string, mixed>>, failed_jobs: array<int, array<string, mixed>>}
     */
    private function buildQueueSnapshot(): array
    {
        $jobs = [];
        $failed = [];
        $out = [];
        $sms = [];

        try {
            $rows = DB::table('jobs')->orderByDesc('id')->limit(25)->get();
            foreach ($rows as $row) {
                $ca = (int) ($row->created_at ?? 0);
                $aa = (int) ($row->available_at ?? 0);
                $jobs[] = [
                    'id' => (int) $row->id,
                    'queue' => (string) $row->queue,
                    'attempts' => (int) $row->attempts,
                    'created_at_display' => $ca > 0 ? DateDisplay::formatHumanFromUnix($ca, true) : '—',
                    'available_at_display' => $aa > 0 ? DateDisplay::formatHumanFromUnix($aa, true) : '—',
                    'label' => $this->summarizeJobPayload($row->payload ?? null),
                ];
            }
        } catch (\Throwable) {
        }

        try {
            $frows = DB::table('failed_jobs')->orderByDesc('id')->limit(12)->get();
            foreach ($frows as $row) {
                $fd = DateDisplay::carbon($row->failed_at ?? null);
                $failed[] = [
                    'id' => (int) $row->id,
                    'queue' => (string) $row->queue,
                    'failed_at_display' => $fd ? DateDisplay::formatHuman($fd, true) : '—',
                    'label' => $this->summarizeJobPayload($row->payload ?? null),
                    'exception' => mb_substr((string) ($row->exception ?? ''), 0, 400),
                ];
            }
        } catch (\Throwable) {
        }

        try {
            foreach (OutboundDelivery::query()->orderByDesc('updated_at')->limit(18)->get() as $d) {
                $out[] = [
                    'id' => $d->id,
                    'event_id' => (string) $d->event_id,
                    'status' => (string) ($d->delivery_status ?? ''),
                    'attempts' => (int) $d->attempts,
                    'http' => $d->last_http_status,
                    'delivered_at_display' => $d->delivered_at ? DateDisplay::formatHuman($d->delivered_at, true) : '—',
                    'next_retry_at_display' => $d->next_retry_at ? DateDisplay::formatHuman($d->next_retry_at, true) : '—',
                    'error' => $d->last_error ? mb_substr((string) $d->last_error, 0, 500) : null,
                ];
            }
        } catch (\Throwable) {
        }

        try {
            foreach (SmsDelivery::query()->orderByDesc('updated_at')->limit(18)->get() as $d) {
                $sms[] = [
                    'id' => $d->id,
                    'event_id' => (string) $d->event_id,
                    'status' => (string) ($d->status ?? ''),
                    'attempts' => (int) $d->attempts,
                    'http' => $d->last_http_status,
                    'sent_at_display' => $d->sent_at ? DateDisplay::formatHuman($d->sent_at, true) : '—',
                    'next_retry_at_display' => $d->next_retry_at ? DateDisplay::formatHuman($d->next_retry_at, true) : '—',
                    'error' => $d->last_error ? mb_substr((string) $d->last_error, 0, 500) : null,
                ];
            }
        } catch (\Throwable) {
        }

        return [
            'jobs' => $jobs,
            'failed_jobs' => $failed,
            'outbound' => $out,
            'sms' => $sms,
        ];
    }

    private function shortenJsonBodyPretty(string $raw, int $maxLen = 1200): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }
        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $raw = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        }
        if (mb_strlen($raw) <= $maxLen) {
            return $raw;
        }

        return mb_substr($raw, 0, $maxLen).'…';
    }

    private function summarizeJobPayload(mixed $payload): string
    {
        if (! is_string($payload) || $payload === '') {
            return '—';
        }
        $j = json_decode($payload, true);
        if (! is_array($j)) {
            return mb_substr($payload, 0, 72);
        }
        $name = $j['displayName'] ?? data_get($j, 'data.commandName') ?? data_get($j, 'data.displayName') ?? 'job';
        $name = str_replace('\\\\', '\\', (string) $name);

        return class_basename($name);
    }

    public function test(Request $request, string $key)
    {
        $timeout = (int) $request->input('timeout', 10);
        if ($timeout <= 0) {
            $timeout = 10;
        }
        if ($timeout > 60) {
            $timeout = 60;
        }

        $lane = (string) $request->input('lane', 'out');
        if (! in_array($lane, ['in', 'out'], true)) {
            $lane = 'out';
        }

        $integration = Integration::query()->where('key', $key)->first();
        if (! $integration) {
            return back()->with([
                'overview_last_test_key' => $key,
                'overview_last_test' => [
                    'ok' => false,
                    'steps' => [
                        ['name' => 'Carregar integração', 'ok' => false, 'message' => 'Integração não existe no banco.'],
                    ],
                ],
            ]);
        }

        $steps = [];
        $okAll = true;

        $steps[] = [
            'name' => 'Configuração básica',
            'ok' => true,
            'message' => 'enabled='.($integration->enabled ? 'true' : 'false').', base_url='.(string) ($integration->base_url ?? '(vazio)'),
        ];

        try {
            if ($key === 'gestor') {
                if ($lane === 'in') {
                    $base = rtrim((string) ($integration->base_url ?? ''), '/');
                    $hmac = (string) ($integration->hmac_secret ?? '');
                    $steps[] = [
                        'name' => 'Recepção: URL base (Gestor → GIDE)',
                        'ok' => $base !== '',
                        'message' => $base !== '' ? $base : 'base_url vazia (webhooks precisam bater no host certo).',
                    ];
                    $steps[] = [
                        'name' => 'Recepção: segredo HMAC (assinatura inbound)',
                        'ok' => $hmac !== '',
                        'message' => $hmac !== '' ? 'configurado' : 'não configurado',
                    ];
                    if ($base === '' || $hmac === '') {
                        $okAll = false;
                    }
                } else {
                    $client = new GestorClient($integration);
                    $token = $client->signIn();
                    $steps[] = ['name' => 'Saída: Signin (GIDE → Gestor)', 'ok' => true, 'message' => 'token='.mb_substr($token, 0, 12).'…'];
                }
            } elseif ($key === 'ieducar') {
                if ($lane === 'in') {
                    $hmac = (string) ($integration->hmac_secret ?? '');
                    $steps[] = [
                        'name' => 'Recepção: segredo HMAC (iEducar → GIDE)',
                        'ok' => $hmac !== '',
                        'message' => $hmac !== '' ? 'configurado' : 'não configurado',
                    ];
                    if ($hmac === '') {
                        $okAll = false;
                    }
                } else {
                    // Saída: reachability da base_url (chamadas GIDE → iEducar).
                    $base = rtrim((string) ($integration->base_url ?? ''), '/');
                    if ($base === '') {
                        throw new \RuntimeException('base_url vazia.');
                    }
                    $resp = Http::timeout($timeout)->get($base);
                    $steps[] = ['name' => 'Saída: reachability (base_url)', 'ok' => $resp->status() < 500, 'message' => 'HTTP '.$resp->status()];
                    if ($resp->status() >= 500) {
                        $okAll = false;
                    }
                }
            } elseif ($key === 'sms') {
                if ($lane === 'in') {
                    $steps[] = [
                        'name' => 'Recepção (→ GIDE)',
                        'ok' => true,
                        'message' => 'Fluxo típico só de saída (GIDE → provedor). Não há webhook inbound nesta integração.',
                    ];
                } else {
                    $base = rtrim((string) ($integration->base_url ?? ''), '/');
                    $token = (string) ($integration->auth_token ?? '');
                    if ($base === '') {
                        throw new \RuntimeException('base_url vazia.');
                    }
                    if ($token === '') {
                        throw new \RuntimeException('api_token vazio.');
                    }
                    $resp = Http::timeout($timeout)
                        ->withToken($token)
                        ->withHeaders(['Accept' => 'application/json'])
                        ->get($base);
                    $steps[] = ['name' => 'Saída: reachability (auth)', 'ok' => $resp->status() < 500, 'message' => 'HTTP '.$resp->status()];
                    if ($resp->status() >= 500) {
                        $okAll = false;
                    }
                }
            } elseif ($key === 'catraca_frequencia') {
                $token = (string) ($integration->auth_token ?? '');
                $steps[] = [
                    'name' => 'Saída: Bearer API catraca-frequência (GIDE → iEducar)',
                    'ok' => $token !== '',
                    'message' => $token !== '' ? 'configurado' : 'não configurado',
                ];
                if ($token === '') {
                    $okAll = false;
                }
            } else {
                $steps[] = ['name' => 'Teste', 'ok' => false, 'message' => 'Sem teste automático para esta integração.'];
                $okAll = false;
            }
        } catch (\Throwable $e) {
            $steps[] = ['name' => 'Teste', 'ok' => false, 'message' => $e->getMessage()];
            $okAll = false;
        }

        $now = now();

        return back()->with([
            'overview_last_test_key' => $key,
            'overview_last_test' => [
                'ok' => $okAll,
                'timeout' => $timeout,
                'lane' => $lane,
                'steps' => $steps,
                'tested_at' => $now->toIso8601String(),
                'tested_at_display' => DateDisplay::formatHuman($now, true),
            ],
        ]);
    }
}
