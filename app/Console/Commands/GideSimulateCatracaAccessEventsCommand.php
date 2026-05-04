<?php

namespace App\Console\Commands;

use App\Models\GestorAccessEventDelivery;
use App\Models\Integration;
use App\Support\GestorCatracaAccessToken;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Throwable;

class GideSimulateCatracaAccessEventsCommand extends Command
{
    protected $signature = 'gide:simulate-catraca-access-events
                            {--count=12 : Número de POSTs (mínimo 10)}
                            {--token= : Bearer em texto plano; senão usa a variável de ambiente GIDE_CATRACA_ACCESS_TOKEN}
                            {--http : Envia pedidos por HTTP à base URL em vez de usar o kernel interno}
                            {--url= : Base URL com --http (default: APP_URL)}';

    protected $description = 'Simula POSTs em /api/v1/catraca/access-events (≥10) para validar a API e a auditoria em gestor_access_event_deliveries';

    public function handle(): int
    {
        $count = max(10, (int) $this->option('count'));
        $token = trim((string) ($this->option('token') ?: (string) env('GIDE_CATRACA_ACCESS_TOKEN', '')));
        if ($token === '') {
            $this->error('Informe --token=... ou defina GIDE_CATRACA_ACCESS_TOKEN (token mostrado uma vez em Integrações → Gestor).');

            return self::FAILURE;
        }

        $gestor = Integration::query()->where('key', 'gestor')->first();
        if (! $gestor) {
            $this->error('Integração gestor inexistente. Crie em Integrações → Gestor.');

            return self::FAILURE;
        }
        if (! $gestor->enabled) {
            $this->error('Integração gestor desabilitada. Habilite antes de simular.');

            return self::FAILURE;
        }
        if (! GestorCatracaAccessToken::isConfigured($gestor)) {
            $this->error('Token de acesso da catraca não configurado. Gere em Integrações → Gestor.');

            return self::FAILURE;
        }
        if (! GestorCatracaAccessToken::checkPlainAgainstIntegration($token, $gestor)) {
            $this->warn('O token não confere com o hash na base; espere 401 em todos os pedidos.');
        }

        $useHttp = (bool) $this->option('http');
        $baseUrl = rtrim((string) ($this->option('url') ?: config('app.url', 'http://localhost')), '/');

        $prefix = now()->format('YmdHis');
        $eventIds = [];
        $rows = [];

        $this->info(sprintf('A enviar %d POST(s) para /api/v1/catraca/access-events (%s).', $count, $useHttp ? 'HTTP '.$baseUrl : 'kernel interno'));
        $this->newLine();

        for ($i = 0; $i < $count; $i++) {
            $eventId = sprintf('gide-sim-%s-%03d', $prefix, $i);
            $eventIds[] = $eventId;
            $payload = $this->samplePayload($eventId, $i);

            try {
                if ($useHttp) {
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
                    $json = is_array($decoded) ? $decoded : [];
                    $kernel->terminate($request, $response);
                }
            } catch (Throwable $e) {
                $status = 0;
                $json = ['message' => $e->getMessage()];
            }

            $rows[] = [
                'i' => $i + 1,
                'http' => $status,
                'ok' => ($json['ok'] ?? null) === true ? 'sim' : 'não',
                'created' => ($json['created'] ?? null) === true ? 'sim' : (($json['created'] ?? null) === false ? 'não' : '—'),
                'delivery_id' => (string) ($json['delivery_id'] ?? '—'),
                'eventId' => $eventId,
            ];
        }

        $this->table(
            ['#', 'HTTP', 'ok', 'created', 'delivery_id', 'event_id'],
            array_map(fn (array $r) => [$r['i'], $r['http'], $r['ok'], $r['created'], $r['delivery_id'], $r['eventId']], $rows),
        );

        $this->newLine();
        $this->info('Auditoria (gestor_access_event_deliveries, canal catraca_bearer) para estes event_id:');

        $audit = GestorAccessEventDelivery::query()
            ->where('inbound_channel', GestorAccessEventDelivery::CHANNEL_CATRACA_BEARER)
            ->whereIn('event_id', $eventIds)
            ->orderBy('id')
            ->get(['id', 'event_id', 'processing_status', 'access_event_was_created', 'created_at']);

        if ($audit->isEmpty()) {
            $this->warn('Nenhuma linha encontrada (verifique erros HTTP acima).');
        } else {
            $this->table(
                ['id', 'event_id', 'processing_status', 'access_event_novo', 'created_at'],
                $audit->map(fn (GestorAccessEventDelivery $d) => [
                    $d->id,
                    $d->event_id,
                    $d->processing_status,
                    $d->access_event_was_created ? 'sim' : 'não',
                    $d->created_at?->toDateTimeString() ?? '',
                ])->all(),
            );
            $this->line('Detalhe no browser: /admin/gestor-access-events');
        }

        $failed = collect($rows)->filter(fn (array $r) => (int) $r['http'] !== 200)->count();
        if ($failed > 0) {
            $this->warn($failed.' pedido(s) com HTTP ≠ 200.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    private function samplePayload(string $eventId, int $index): array
    {
        return [
            'eventId' => $eventId,
            'creationDate' => now()->subMinutes($index % 5)->utc()->format('Y-m-d\TH:i:s\Z'),
            'name' => (string) (897500 + $index),
            'profile' => 'guest',
            'place' => 'Portaria Principal',
            'unity' => 'Aluno',
            'unityGroup' => 'Escola',
            'condominium' => 'Escola simulação CLI',
            'way' => $index % 2 === 0 ? 'Entrance' : 'Exit',
            'accessMedia' => 'facial',
        ];
    }
}
