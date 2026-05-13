<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\GestorAccessEventDelivery;
use App\Models\Integration;
use App\Models\OutboundDelivery;
use App\Models\SmsDelivery;
use App\Models\UserIntegrationOverviewState;
use App\Services\Gestor\GestorClient;
use App\Services\Ieducar\IeducarClient;
use App\Services\Sms\TwilioSmsClient;
use App\Services\UserAuditLogger;
use App\Support\DateDisplay;
use App\Support\GestorSigninProbeCache;
use App\Support\Ieducar\GideFrequenciaRegistroPlanB;
use App\Support\OutboundDeliveryStatuses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class IntegrationOverviewController extends Controller
{
    private const CATRACA_PREVIEW_CACHE_KEY = 'integration.catraca_freq_preview_v1';

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

        $ov = $this->overviewUserState($request);
        $storedOverview = $ov['stored'];
        $laneTests = $ov['lane_tests'];

        $dbMetrics = $this->collectDbMetrics();

        $queueSnapshot = $this->buildQueueSnapshot();

        $ieducarRow = $byKey->get('ieducar');
        $catracaFrequenciaPreviewProbe = ($ieducarRow && $ieducarRow->exists && empty($laneTests['catraca_frequencia:out']))
            ? $this->rememberedCatracaFrequenciaPreviewProbe($ieducarRow)
            : null;

        $laneTestsForBridge = $laneTests;
        if ($catracaFrequenciaPreviewProbe !== null && empty($laneTests['catraca_frequencia:out'])) {
            $laneTestsForBridge['catraca_frequencia:out'] = $catracaFrequenciaPreviewProbe;
        }

        $bridgeHealth = $this->computeBridgeHealth(
            $dbMetrics,
            $queueSnapshot,
            $byKey->get('ieducar'),
            $byKey->get('gestor'),
            $byKey->get('sms'),
            $laneTestsForBridge,
        );

        $integrationCards = collect(['ieducar', 'gestor', 'sms'])
            ->map(fn (string $k) => $byKey->get($k))
            ->filter()
            ->values();

        $lastTest = session('overview_last_test');
        if (! is_array($lastTest) && $storedOverview && is_array($storedOverview->last_test)) {
            $lastTest = $storedOverview->last_test;
        }
        $lastTestKey = session('overview_last_test_key');
        if (! is_string($lastTestKey) || $lastTestKey === '') {
            $lastTestKey = ($storedOverview && is_string($storedOverview->last_test_key)) ? $storedOverview->last_test_key : null;
        }

        return view('integrations.overview', [
            'items' => $items,
            'integrationCards' => $integrationCards,
            'catracaFrequencia' => $byKey->get('catraca_frequencia'),
            'lastTest' => is_array($lastTest) ? $lastTest : null,
            'lastTestKey' => $lastTestKey !== null && $lastTestKey !== '' ? $lastTestKey : null,
            'laneTests' => $laneTests,
            'catracaFrequenciaPreviewProbe' => $catracaFrequenciaPreviewProbe,
            'metrics' => $metrics,
            'dbMetrics' => $dbMetrics,
            'queueSnapshot' => $queueSnapshot,
            'connectionTone' => $bridgeHealth['tone'],
            'mapSegmentTones' => $bridgeHealth['tones'],
            'smsChainReady' => $smsChainReady,
            'smsConfigured' => $smsConfigured,
            'gestorConfigured' => $gestorConfigured,
            'gestorEnabled' => $gestorEnabled,
            'smsEnabled' => $smsEnabled,
            'integrationsOverviewAdmin' => (bool) $request->user()->is_admin,
        ]);
    }

    public function status(Request $request): JsonResponse
    {
        $laneTests = $this->overviewUserState($request)['lane_tests'];

        $dbMetrics = $this->collectDbMetrics();
        $queueSnapshot = $this->buildQueueSnapshot();
        $byKey = Integration::query()->whereIn('key', ['ieducar', 'gestor', 'sms'])->get()->keyBy('key');

        $ieducar = $byKey->get('ieducar');
        $laneTestsForBridge = $laneTests;
        if ($ieducar && $ieducar->exists && empty($laneTests['catraca_frequencia:out'])) {
            $probe = $this->rememberedCatracaFrequenciaPreviewProbe($ieducar);
            if (is_array($probe) && array_key_exists('ok', $probe)) {
                $laneTestsForBridge['catraca_frequencia:out'] = $probe;
            }
        }

        $health = $this->computeBridgeHealth(
            $dbMetrics,
            $queueSnapshot,
            $ieducar,
            $byKey->get('gestor'),
            $byKey->get('sms'),
            $laneTestsForBridge,
        );

        return response()->json([
            'tone' => $health['tone'],
            'tones' => $health['tones'],
            'checked_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function collectDbMetrics(): array
    {
        try {
            $maxAttempts = max(1, (int) config('gide.deliveries.max_attempts', 3));

            $smsRetryDue = (int) SmsDelivery::query()
                ->whereNull('sent_at')
                ->where('attempts', '<', $maxAttempts)
                ->whereNotNull('next_retry_at')
                ->where('next_retry_at', '<=', now())
                ->count();

            return [
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
                'gestor_access_event_ieducar_pending' => (int) GestorAccessEventDelivery::query()
                    ->where('processing_status', GestorAccessEventDelivery::STATUS_PENDING)
                    ->count(),
            ];
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Estado persistido + sessão (mesma base dos cartões de teste por faixa).
     *
     * @return array{stored: UserIntegrationOverviewState|null, lane_tests: array<string, mixed>}
     */
    private function overviewUserState(Request $request): array
    {
        $stored = null;
        $user = $request->user();
        if ($user) {
            $stored = UserIntegrationOverviewState::query()->where('user_id', $user->getKey())->first();
        }

        $dbLanes = $stored && is_array($stored->lane_tests) ? $stored->lane_tests : [];
        $laneTests = array_merge($dbLanes, session('overview_lane_tests', []));

        return ['stored' => $stored, 'lane_tests' => $laneTests];
    }

    /**
     * @param  array<string, mixed>  $laneTests
     * @param  list<string>  $keys
     */
    private function bridgeSegmentReflectsLaneFailures(array $laneTests, array $keys): bool
    {
        foreach ($keys as $key) {
            $entry = $laneTests[$key] ?? null;
            if (is_array($entry) && array_key_exists('ok', $entry) && $entry['ok'] === false) {
                return true;
            }
        }

        return false;
    }

    private function bridgeToneMax(string $a, string $b): string
    {
        $r = max($this->bridgeToneRank($a), $this->bridgeToneRank($b));

        return $this->bridgeToneFromRank($r);
    }

    /**
     * @param  array<string, mixed>  $laneTests  Resultados dos botões “testar” por faixa (cartões), mesclados DB+sessão.
     * @return array{tone: string, tones: array{ieducar: string, gestor: string}}
     */
    private function computeBridgeHealth(array $dbMetrics, array $queueSnapshot, ?Integration $ieducar, ?Integration $gestor, ?Integration $sms, array $laneTests = []): array
    {
        $bad = static function (): array {
            return ['tone' => 'bad', 'tones' => ['ieducar' => 'bad', 'gestor' => 'bad']];
        };

        if (! empty($dbMetrics['error'])) {
            return $bad();
        }

        if (count($queueSnapshot['failed_jobs'] ?? []) > 0) {
            return $bad();
        }

        $configured = function (?Integration $i): bool {
            if (! $i) {
                return false;
            }
            $hasBase = is_string($i->base_url ?? null) && (string) $i->base_url !== '';
            $hasAuthToken = is_string($i->auth_token ?? null) && (string) $i->auth_token !== '';
            $hasHmac = is_string($i->hmac_secret ?? null) && (string) $i->hmac_secret !== '';

            return $hasBase || $hasAuthToken || $hasHmac || ! empty($i->extra);
        };

        $pendingJobs = (int) ($dbMetrics['jobs_pending'] ?? 0);
        $jobBacklogWarn = $pendingJobs > 8;

        $outboundRetryDue = (int) ($dbMetrics['outbound_retry_due'] ?? 0) > 0;
        $outboundFailed = (int) ($dbMetrics['outbound_failed'] ?? 0) > 0;
        $gestorProbeWarn = GestorSigninProbeCache::hasRecentFailure();

        // iEducar: não misturar fila genérica de jobs com o tronco GIDE↔iEducar (gerava aviso no mapa com cartões OK).
        $segIeducarBase = ! $configured($ieducar) ? 'warn' : 'ok';
        $ieducarLanesFailed = $this->bridgeSegmentReflectsLaneFailures($laneTests, ['ieducar:in', 'ieducar:out', 'catraca_frequencia:out']);
        $segIeducar = $ieducarLanesFailed ? $this->bridgeToneMax($segIeducarBase, 'bad') : $segIeducarBase;

        $gaeIeducarPending = (int) ($dbMetrics['gestor_access_event_ieducar_pending'] ?? 0) > 0;

        $segGestorBase = (! $configured($gestor) || $jobBacklogWarn || $gestorProbeWarn || $outboundFailed > 0 || $outboundRetryDue || $gaeIeducarPending) ? 'warn' : 'ok';
        $gestorLanesFailed = $this->bridgeSegmentReflectsLaneFailures($laneTests, ['gestor:in', 'gestor:out']);
        $segGestor = $gestorLanesFailed ? $this->bridgeToneMax($segGestorBase, 'bad') : $segGestorBase;

        $smsSurfaceWarn = ($sms && (bool) ($sms->enabled ?? false) && ! $configured($sms))
            || ((int) ($dbMetrics['sms_retry_due'] ?? 0) > 0);

        $opsBacklogRank = $jobBacklogWarn ? 1 : 0;

        $surfaceRank = max(
            $this->bridgeToneRank($segIeducar),
            $this->bridgeToneRank($segGestor),
            $smsSurfaceWarn ? 1 : 0,
            $opsBacklogRank,
        );

        return [
            'tone' => $this->bridgeToneFromRank($surfaceRank),
            'tones' => [
                'ieducar' => $segIeducar,
                'gestor' => $segGestor,
            ],
        ];
    }

    private function bridgeToneRank(string $tone): int
    {
        return match ($tone) {
            'bad' => 2,
            'warn' => 1,
            default => 0,
        };
    }

    private function bridgeToneFromRank(int $rank): string
    {
        return match (true) {
            $rank >= 2 => 'bad',
            $rank >= 1 => 'warn',
            default => 'ok',
        };
    }

    /**
     * Teste ida/volta GIDE ↔ iEducar (HTTP host + API catraca-frequência com Bearer).
     */
    public function bridgeProbeIeducar(Request $request): JsonResponse
    {
        $timeout = $this->clampTimeout((int) $request->input('timeout', 12));
        $integration = Integration::query()->where('key', 'ieducar')->first();
        if (! $integration) {
            return $this->bridgeErrorJson('Integração ieducar não existe no banco.', 'ieducar');
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

        UserAuditLogger::recordAuthenticated('integration.bridge.probe', [
            'target' => 'ieducar',
            'ok' => $okAll,
            'timeout' => $timeout,
            'steps' => count($steps),
        ], 'integration', $integration->id);

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
            return $this->bridgeErrorJson('Integração gestor não existe no banco.', 'gestor');
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

        if ($okAll) {
            GestorSigninProbeCache::recordSuccess();
        } else {
            GestorSigninProbeCache::recordFailure();
        }

        UserAuditLogger::recordAuthenticated('integration.bridge.probe', [
            'target' => 'gestor',
            'ok' => $okAll,
            'timeout' => $timeout,
            'steps' => count($steps),
        ], 'integration', $integration->id);

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
            return $this->bridgeErrorJson('Integração sms não existe no banco.', 'sms');
        }

        $steps = [];
        $okAll = true;

        try {
            $provider = strtolower(trim((string) data_get($integration->extra, 'provider', config('integrations.sms.default_provider', 'twilio'))));

            if ($provider === 'zenvia') {
                $base = rtrim((string) ($integration->base_url ?? ''), '/');
                if ($base === '') {
                    $base = rtrim((string) config('integrations.sms.default_base_url'), '/');
                }
                $apiToken = (string) ($integration->auth_token ?? '');
                if ($apiToken === '') {
                    throw new \RuntimeException('Token API vazio (integrations.auth_token).');
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
                    'name' => '1. SMS (Zenvia) — GET listagem de mensagens',
                    'direction' => 'gide_saida_get_x_api_token_provedor_sms_resposta',
                    'detail' => 'Usa integrations.base_url (ou padrão v2) e header X-API-TOKEN. Caminho: '.$path.'.',
                    'ok' => $resp->status() < 500,
                    'message' => 'HTTP '.$resp->status().($snippet !== '' ? ' · corpo: '.preg_replace('/\s+/', ' ', $snippet) : ''),
                ];
                if ($resp->status() >= 500) {
                    $okAll = false;
                }
            } else {
                $authToken = (string) ($integration->auth_token ?? '');
                if ($authToken === '') {
                    throw new \RuntimeException('Auth Token Twilio vazio (integrations.auth_token).');
                }

                $accountSid = TwilioSmsClient::normalizeTwilioAccountSid((string) data_get($integration->extra, 'account_sid', ''));
                if ($accountSid === '' || ! preg_match('/^AC[0-9a-f]{32}$/i', $accountSid)) {
                    throw new \RuntimeException('Account SID Twilio não configurado ou inválido (integrations.extra.account_sid).');
                }

                $url = TwilioSmsClient::accountJsonProbeUrl($integration);
                $resp = Http::timeout($timeout)
                    ->withBasicAuth($accountSid, $authToken)
                    ->acceptJson()
                    ->get($url);

                $snippet = $this->shortenJsonBodyPretty((string) $resp->body(), 900);
                $steps[] = [
                    'name' => '1. SMS (Twilio) — GET Account (validação Basic Auth)',
                    'direction' => 'gide_saida_get_twilio_account_json',
                    'detail' => 'GET '.$url.' (mesma raiz API que o envio de mensagens).',
                    'ok' => $resp->status() < 500,
                    'message' => 'HTTP '.$resp->status().($snippet !== '' ? ' · corpo: '.preg_replace('/\s+/', ' ', $snippet) : ''),
                ];
                if ($resp->status() >= 500) {
                    $okAll = false;
                }
            }
        } catch (\Throwable $e) {
            $steps[] = [
                'name' => 'Falha no teste da ponte SMS',
                'direction' => 'erro_http_ou_config_sms',
                'detail' => 'Revise credenciais e provedor em /integracoes/sms.',
                'ok' => false,
                'message' => $e->getMessage(),
            ];
            $okAll = false;
        }

        UserAuditLogger::recordAuthenticated('integration.bridge.probe', [
            'target' => 'sms',
            'ok' => $okAll,
            'timeout' => $timeout,
            'steps' => count($steps),
        ], 'integration', $integration->id);

        return $this->bridgeJsonResponse($okAll, $steps);
    }

    private function bridgeJsonResponse(bool $ok, array $steps): JsonResponse
    {
        $now = now();

        return response()->json([
            'ok' => $ok,
            'probe_state' => $ok ? 'ok' : 'error',
            'steps' => $steps,
            'tested_at' => $now->toIso8601String(),
            'tested_at_display' => DateDisplay::formatHuman($now, true),
        ]);
    }

    private function bridgeErrorJson(string $message, ?string $probeTarget = null): JsonResponse
    {
        if ($probeTarget !== null) {
            UserAuditLogger::recordAuthenticated('integration.bridge.probe', [
                'target' => $probeTarget,
                'ok' => false,
                'error' => $message,
            ], 'integration', null);
        }

        $now = now();

        return response()->json([
            'ok' => false,
            'probe_state' => 'unconfigured',
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
     * @return array{jobs: array<int, array<string, mixed>>, outbound: array<int, array<string, mixed>>, sms: array<int, array<string, mixed>>, failed_jobs: array<int, array<string, mixed>>, gestor_access_events: array<int, array<string, mixed>>}
     */
    private function buildQueueSnapshot(): array
    {
        $jobs = [];
        $failed = [];
        $out = [];
        $sms = [];
        $gae = [];

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

        try {
            foreach (GestorAccessEventDelivery::query()
                ->whereIn('processing_status', [
                    GestorAccessEventDelivery::STATUS_PENDING,
                    GestorAccessEventDelivery::STATUS_FAILED,
                    GestorAccessEventDelivery::STATUS_PROCESSING,
                ])
                ->orderByDesc('id')
                ->limit(18)
                ->get() as $d) {
                $gae[] = [
                    'id' => $d->id,
                    'event_id' => (string) $d->event_id,
                    'status' => (string) ($d->processing_status ?? ''),
                    'channel' => (string) ($d->inbound_channel ?? ''),
                    'attempts' => (int) ($d->ieducar_attempts ?? 0),
                    'http' => $d->ieducar_frequencia_http_status,
                    'processed_at_display' => $d->processed_at ? DateDisplay::formatHuman($d->processed_at, true) : '—',
                    'error' => $d->ieducar_frequencia_error ? mb_substr((string) $d->ieducar_frequencia_error, 0, 500) : null,
                ];
            }
        } catch (\Throwable) {
        }

        return [
            'jobs' => $jobs,
            'failed_jobs' => $failed,
            'outbound' => $out,
            'sms' => $sms,
            'gestor_access_events' => $gae,
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
            $now = now();
            UserAuditLogger::recordAuthenticated('integration.overview.test', [
                'key' => $key,
                'lane' => $lane,
                'ok' => false,
                'timeout' => $timeout,
                'reason' => 'integration_missing',
            ], 'integration', null);
            $this->mergeOverviewLaneTestRecord($key, $lane, false, $now);

            $lastPayload = [
                'ok' => false,
                'timeout' => $timeout,
                'lane' => $lane,
                'steps' => [
                    ['name' => 'Carregar integração', 'ok' => false, 'message' => 'Integração não existe no banco.'],
                ],
                'tested_at' => $now->toIso8601String(),
                'tested_at_display' => DateDisplay::formatHuman($now, true),
            ];
            $this->persistUserOverviewTestState($key, $lastPayload);

            return back()->with([
                'overview_last_test_key' => $key,
                'overview_last_test' => $lastPayload,
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
                    GestorSigninProbeCache::recordSuccess();
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
                    $provider = strtolower(trim((string) data_get($integration->extra, 'provider', config('integrations.sms.default_provider', 'twilio'))));
                    $token = (string) ($integration->auth_token ?? '');

                    if ($provider === 'zenvia') {
                        if ($token === '') {
                            throw new \RuntimeException('Token API vazio (integrations.auth_token).');
                        }
                        $base = rtrim((string) ($integration->base_url ?? ''), '/');
                        if ($base === '') {
                            $base = rtrim((string) config('integrations.sms.default_base_url'), '/');
                        }
                        $path = '/channels/sms/messages?limit=1';
                        $resp = Http::timeout($timeout)
                            ->withHeaders([
                                'X-API-TOKEN' => $token,
                                'Accept' => 'application/json',
                            ])
                            ->get($base.$path);
                        $steps[] = [
                            'name' => 'Saída: Zenvia GET mensagens (X-API-TOKEN)',
                            'ok' => $resp->status() < 500,
                            'message' => 'HTTP '.$resp->status().' · '.$base.$path,
                        ];
                        if ($resp->status() >= 500) {
                            $okAll = false;
                        }
                    } else {
                        if ($token === '') {
                            throw new \RuntimeException('Auth Token Twilio vazio (integrations.auth_token).');
                        }
                        $sid = TwilioSmsClient::normalizeTwilioAccountSid((string) data_get($integration->extra, 'account_sid', ''));
                        if ($sid === '' || ! preg_match('/^AC[0-9a-f]{32}$/i', $sid)) {
                            throw new \RuntimeException('Account SID Twilio não configurado ou inválido (integrations.extra.account_sid).');
                        }
                        $url = TwilioSmsClient::accountJsonProbeUrl($integration);
                        $resp = Http::timeout($timeout)
                            ->withBasicAuth($sid, $token)
                            ->acceptJson()
                            ->get($url);
                        $steps[] = [
                            'name' => 'Saída: Twilio GET Account (Basic Auth)',
                            'ok' => $resp->status() < 500,
                            'message' => 'HTTP '.$resp->status().' · '.$url,
                        ];
                        if ($resp->status() >= 500) {
                            $okAll = false;
                        }
                    }
                }
            } elseif ($key === 'catraca_frequencia') {
                $inboundTok = (string) ($integration->auth_token ?? '');
                $steps[] = [
                    'name' => 'Integração dedicada catraca_frequencia (token inbound, opcional)',
                    'ok' => true,
                    'message' => $inboundTok !== ''
                        ? 'Bearer inbound configurado (recepção iEducar → GIDE).'
                        : 'Sem token inbound: só a saída GIDE→iEducar é necessária para o preview.',
                ];
                $ieducar = Integration::query()->where('key', 'ieducar')->first();
                if (! $ieducar || ! $ieducar->exists) {
                    $steps[] = [
                        'name' => 'POST frequencia/registro (preview)',
                        'ok' => false,
                        'message' => 'Integração ieducar não encontrada no banco.',
                    ];
                    $okAll = false;
                } else {
                    $preview = $this->runCatracaFrequenciaPreviewAgainstIeducar($ieducar);
                    $steps[] = [
                        'name' => 'POST frequencia/registro (preview, plano B — mesmo payload da fila admin)',
                        'ok' => $preview['ok'],
                        'message' => $preview['message'],
                    ];
                    if (! $preview['ok']) {
                        $okAll = false;
                    }
                }
                Cache::forget(self::CATRACA_PREVIEW_CACHE_KEY);
            } else {
                $steps[] = ['name' => 'Teste', 'ok' => false, 'message' => 'Sem teste automático para esta integração.'];
                $okAll = false;
            }
        } catch (\Throwable $e) {
            if ($key === 'gestor' && $lane === 'out') {
                GestorSigninProbeCache::recordFailure();
            }
            $steps[] = ['name' => 'Teste', 'ok' => false, 'message' => $e->getMessage()];
            $okAll = false;
        }

        $now = now();
        UserAuditLogger::recordAuthenticated('integration.overview.test', [
            'key' => $key,
            'lane' => $lane,
            'ok' => $okAll,
            'timeout' => $timeout,
            'steps' => count($steps),
        ], 'integration', $integration->id);
        $this->mergeOverviewLaneTestRecord($key, $lane, $okAll, $now);

        $lastPayload = [
            'ok' => $okAll,
            'timeout' => $timeout,
            'lane' => $lane,
            'steps' => $steps,
            'tested_at' => $now->toIso8601String(),
            'tested_at_display' => DateDisplay::formatHuman($now, true),
        ];
        $this->persistUserOverviewTestState($key, $lastPayload);

        return back()->with([
            'overview_last_test_key' => $key,
            'overview_last_test' => $lastPayload,
        ]);
    }

    /**
     * Guarda na sessão o resultado do último teste manual por direção (in/out) e por chave de integração.
     *
     * @param  string  $integrationKey  ex.: ieducar, gestor, sms, catraca_frequencia
     */
    private function mergeOverviewLaneTestRecord(string $integrationKey, string $lane, bool $ok, Carbon $at): void
    {
        $mapKey = $integrationKey === 'catraca_frequencia'
            ? 'catraca_frequencia:out'
            : $integrationKey.':'.(in_array($lane, ['in', 'out'], true) ? $lane : 'out');

        $uid = auth()->id();
        $dbLanes = [];
        if (is_int($uid)) {
            $row = UserIntegrationOverviewState::query()->where('user_id', $uid)->first();
            $dbLanes = $row && is_array($row->lane_tests) ? $row->lane_tests : [];
        }

        $prev = array_merge($dbLanes, session('overview_lane_tests', []));
        $prev[$mapKey] = [
            'ok' => $ok,
            'tested_at' => $at->toIso8601String(),
            'tested_at_short' => $at->timezone((string) config('app.timezone', 'America/Sao_Paulo'))->format('d/m H:i'),
        ];
        session(['overview_lane_tests' => $prev]);

        if (is_int($uid)) {
            UserIntegrationOverviewState::query()->updateOrCreate(
                ['user_id' => $uid],
                ['lane_tests' => $prev],
            );
        }

        if ($mapKey === 'catraca_frequencia:out') {
            Cache::forget(self::CATRACA_PREVIEW_CACHE_KEY);
        }
    }

    /**
     * @param  array<string, mixed>  $lastTestPayload
     */
    private function persistUserOverviewTestState(string $lastTestKey, array $lastTestPayload): void
    {
        $uid = auth()->id();
        if (! is_int($uid)) {
            return;
        }

        $laneTests = session('overview_lane_tests', []);
        UserIntegrationOverviewState::query()->updateOrCreate(
            ['user_id' => $uid],
            [
                'lane_tests' => $laneTests,
                'last_test' => $lastTestPayload,
                'last_test_key' => $lastTestKey,
            ],
        );
    }

    /**
     * Mesmo contrato unitário da tela Integrações → frequência e do job (preview): cod_aluno 211, meta.preview=true.
     *
     * @return array{ok: bool, message: string, http: int|null}
     */
    private function runCatracaFrequenciaPreviewAgainstIeducar(Integration $ieducar): array
    {
        $base = rtrim((string) ($ieducar->base_url ?? ''), '/');
        if ($base === '') {
            return ['ok' => false, 'message' => 'base_url do iEducar vazia.', 'http' => null];
        }
        $confirm = (string) data_get($ieducar->extra, 'catraca_frequencia.confirmacao_token', '');
        $bearer = $confirm !== '' ? $confirm : (string) ($ieducar->auth_token ?? '');
        if ($bearer === '') {
            return ['ok' => false, 'message' => 'Bearer ausente: defina o token da API iEducar ou extra.catraca_frequencia.confirmacao_token.', 'http' => null];
        }

        $payload = [
            'meta' => [
                'contract_version' => IeducarClient::CAT_FREQUENCIA_CONTRACT_VERSION,
            ],
            'fonte' => 'gide',
            'presente' => true,
            'identificacao' => [
                'cod_aluno' => 211,
            ],
            'data_ref' => now()->toIso8601String(),
        ];

        try {
            $payload = GideFrequenciaRegistroPlanB::validateAndNormalize($payload);
        } catch (ValidationException $e) {
            $msg = $e->getMessage();
            if ($e->errors()) {
                $msg = collect($e->errors())->flatten()->implode(' ');
            }

            return ['ok' => false, 'message' => 'Validação do payload: '.$msg, 'http' => null];
        }

        $payload = GideFrequenciaRegistroPlanB::refreshDataRefsWithRandomClock($payload);
        $payload['meta']['contract_version'] = IeducarClient::CAT_FREQUENCIA_CONTRACT_VERSION;
        $payload['meta']['preview'] = true;

        try {
            $resp = (new IeducarClient($ieducar))->postCatracaFrequenciaRegistro($payload);
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage(), 'http' => null];
        }

        $http = $resp->status();
        $body = (string) $resp->body();
        $snippet = mb_substr(trim(preg_replace('/\s+/', ' ', $body !== '' ? $body : '')), 0, 280);

        return [
            'ok' => $http < 500,
            'http' => $http,
            'message' => 'HTTP '.$http.($snippet !== '' ? ' · '.$snippet : ''),
        ];
    }

    private function canRunCatracaFrequenciaPreviewOutbound(?Integration $ieducar): bool
    {
        if (! $ieducar || ! $ieducar->exists) {
            return false;
        }
        $base = rtrim((string) ($ieducar->base_url ?? ''), '/');
        if ($base === '') {
            return false;
        }
        $confirm = (string) data_get($ieducar->extra, 'catraca_frequencia.confirmacao_token', '');
        $bearer = $confirm !== '' ? $confirm : (string) ($ieducar->auth_token ?? '');

        return $bearer !== '';
    }

    /**
     * Sonda em cache (15 min) para preencher o estado do Bearer catraca-frequência quando ainda não houve teste manual na sessão.
     *
     * @return array{ok: bool, tested_at_short: string, auto: true, http?: int|null}|null
     */
    private function rememberedCatracaFrequenciaPreviewProbe(?Integration $ieducar): ?array
    {
        if (! $this->canRunCatracaFrequenciaPreviewOutbound($ieducar)) {
            return null;
        }

        return Cache::remember(self::CATRACA_PREVIEW_CACHE_KEY, now()->addMinutes(15), function () use ($ieducar) {
            $r = $this->runCatracaFrequenciaPreviewAgainstIeducar($ieducar);

            return [
                'ok' => $r['ok'],
                'tested_at_short' => now()->timezone((string) config('app.timezone', 'America/Sao_Paulo'))->format('d/m H:i'),
                'auto' => true,
                'http' => $r['http'] ?? null,
            ];
        });
    }
}
