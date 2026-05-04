<?php

namespace Tests\Feature\Api;

use App\Models\GestorAccessEventDelivery;
use App\Models\Integration;
use App\Support\GestorCatracaAccessToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

#[Group('api-catraca-webhook')]
class CatracaAccessWebhookApiTest extends TestCase
{
    use RefreshDatabase;

    private const URL = '/api/v1/catraca/access-events';

    private function gestorWithAccessTokenHash(string $plainToken, ?string $hashKey = null): Integration
    {
        $key = $hashKey ?? GestorCatracaAccessToken::HASH_KEY;

        return Integration::query()->create([
            'key' => 'gestor',
            'name' => 'Gestor',
            'enabled' => true,
            'auth_type' => 'hmac',
            'extra' => [
                $key => Hash::make($plainToken),
            ],
        ]);
    }

    #[TestDox('Webhook catraca: integração Gestor desligada → HTTP 403')]
    public function test_rejects_when_gestor_disabled(): void
    {
        Integration::query()->create([
            'key' => 'gestor',
            'name' => 'Gestor',
            'enabled' => false,
            'auth_type' => 'hmac',
            'extra' => [
                GestorCatracaAccessToken::HASH_KEY => Hash::make('x'),
            ],
        ]);

        $response = $this->withToken('x', 'Bearer')
            ->postJson(self::URL, ['eventId' => 'e1']);

        $this->assertHttpStatusWithReport(
            $response,
            403,
            'POST '.self::URL.' com Bearer válido mas integração gestor disabled=false',
            'Recusar acesso ao webhook quando o Gestor está desabilitado na base.',
            '403 JSON (integração desativada).',
        );
    }

    #[TestDox('Webhook catraca: token não configurado → HTTP 503')]
    public function test_rejects_when_access_token_hash_missing(): void
    {
        Integration::query()->create([
            'key' => 'gestor',
            'name' => 'Gestor',
            'enabled' => true,
            'auth_type' => 'hmac',
            'extra' => [],
        ]);

        $response = $this->withToken('any', 'Bearer')
            ->postJson(self::URL, ['eventId' => 'e1']);

        $this->assertHttpStatusWithReport(
            $response,
            503,
            'POST '.self::URL.' sem catraca_access_token_hash em integrations.extra',
            'Indicar que o token de acesso ainda não foi gerado na UI do Gestor.',
            '503 JSON (token não configurado).',
        );
    }

    #[TestDox('Webhook catraca: hash legado catraca_webhook_bearer_hash ainda aceite')]
    public function test_accepts_legacy_bearer_hash_key(): void
    {
        $token = 'legacy-plain-token';
        $this->gestorWithAccessTokenHash($token, GestorCatracaAccessToken::LEGACY_HASH_KEY);

        $this->withToken($token, 'Bearer')
            ->postJson(self::URL, ['eventId' => 'legacy-evt-1', 'creationDate' => '2026-04-30T12:00:00Z'])
            ->assertOk()
            ->assertJson(['ok' => true, 'created' => true]);
    }

    #[TestDox('Webhook catraca: sem cabeçalho Authorization → HTTP 401')]
    public function test_rejects_missing_bearer(): void
    {
        $this->gestorWithAccessTokenHash('secret-webhook');

        $response = $this->postJson(self::URL, ['eventId' => 'e1']);

        $this->assertHttpStatusWithReport(
            $response,
            401,
            'POST '.self::URL.' sem header Authorization',
            'Exigir Bearer para o webhook da catraca.',
            '401 JSON (Bearer ausente).',
        );
    }

    #[TestDox('Webhook catraca: Bearer incorreto → HTTP 401')]
    public function test_rejects_invalid_bearer(): void
    {
        $this->gestorWithAccessTokenHash('secret-webhook');

        $response = $this->withToken('wrong', 'Bearer')
            ->postJson(self::URL, ['eventId' => 'e1']);

        $this->assertHttpStatusWithReport(
            $response,
            401,
            'POST '.self::URL.' com Bearer que não confere com o hash guardado',
            'Rejeitar token inválido.',
            '401 JSON (token inválido).',
        );
    }

    #[TestDox('Webhook catraca: JSON sem eventId → HTTP 400 e ok=false')]
    public function test_rejects_missing_event_id(): void
    {
        $token = 'secret-webhook';
        $this->gestorWithAccessTokenHash($token);

        $response = $this->withToken($token, 'Bearer')
            ->postJson(self::URL, ['name' => 'x']);

        $this->assertHttpStatusWithReport(
            $response,
            400,
            'POST '.self::URL.' autenticado mas sem campo eventId',
            'Validar corpo mínimo: eventId obrigatório.',
            '400 JSON com ok=false.',
        );

        $this->assertFalse(
            (bool) $response->json('ok'),
            'FALHOU: esperado JSON ok=false. Obtido: '.json_encode($response->json(), JSON_UNESCAPED_UNICODE),
        );

        $this->reportStructuredTestOutcome(
            'Corpo JSON do erro de validação (eventId)',
            'Confirmar campo ok=false na resposta 400.',
            'ok = false',
            'ok = '.json_encode($response->json('ok'), JSON_UNESCAPED_UNICODE),
            'EXITOSO',
        );
    }

    #[TestDox('Webhook catraca: JSON equipamento → delivery_id e inbound_payload na auditoria')]
    public function test_accepts_device_json_and_writes_delivery_audit(): void
    {
        $token = 'catraca-token-'.uniqid('', true);
        $this->gestorWithAccessTokenHash($token);

        Integration::query()->create([
            'key' => 'ieducar',
            'name' => 'iEducar',
            'enabled' => true,
            'auth_type' => 'access_key',
            'base_url' => 'https://ieducar.example.test',
            'extra' => ['presence' => ['windows' => []]],
        ]);

        $eventId = 'a1b2c3d4-e5f6-7890-abcd-ef1234567890';
        $payload = [
            'eventId' => $eventId,
            'creationDate' => '2026-04-30T14:10:36Z',
            'name' => '897555',
            'profile' => 'guest',
            'place' => 'Portaria Principal',
            'unity' => 'Aluno',
            'unityGroup' => 'Escola',
            'condominium' => 'Escola xxx',
            'way' => 'Entrance',
            'accessMedia' => 'facial',
        ];

        $resp = $this->withToken($token, 'Bearer')
            ->postJson(self::URL, $payload)
            ->assertOk()
            ->assertJson(['ok' => true, 'created' => true, 'eventId' => $eventId]);

        $deliveryId = (int) ($resp->json('delivery_id') ?? 0);
        $this->assertGreaterThan(0, $deliveryId);

        $this->assertDatabaseHas('gestor_access_event_deliveries', [
            'id' => $deliveryId,
            'event_id' => $eventId,
            'inbound_channel' => GestorAccessEventDelivery::CHANNEL_CATRACA_BEARER,
        ]);

        $delivery = GestorAccessEventDelivery::query()->findOrFail($deliveryId);
        $this->assertSame($eventId, $delivery->inbound_payload['eventId'] ?? null);
        $this->assertSame('897555', $delivery->inbound_payload['name'] ?? null);
    }

    #[TestDox('Webhook catraca: payload mínimo aceite; segundo POST idempotente')]
    public function test_accepts_minimal_payload_and_is_idempotent(): void
    {
        $token = 'secret-webhook';
        $this->gestorWithAccessTokenHash($token);

        $payload = ['eventId' => 'catraca-evt-unique-1', 'type' => 'entry'];

        $r1 = $this->withToken($token, 'Bearer')
            ->postJson(self::URL, $payload);

        $this->assertHttpStatusWithReport(
            $r1,
            200,
            '1º POST '.self::URL.' com eventId novo',
            'Criar access_event e devolver ok=true, created=true.',
            'HTTP 200; created=true.',
        );

        $this->assertTrue((bool) $r1->json('ok'), 'FALHOU: 1º POST esperado ok=true. Obtido: '.$this->httpResponseLine($r1));
        $this->assertTrue((bool) $r1->json('created'), 'FALHOU: 1º POST esperado created=true. Obtido: '.$this->httpResponseLine($r1));
        $this->assertGreaterThan(0, (int) ($r1->json('delivery_id') ?? 0));

        $r2 = $this->withToken($token, 'Bearer')
            ->postJson(self::URL, $payload);

        $this->assertHttpStatusWithReport(
            $r2,
            200,
            '2º POST '.self::URL.' com o mesmo eventId',
            'Não duplicar registo access_events; created=false.',
            'HTTP 200; created=false.',
        );

        $this->assertFalse((bool) $r2->json('created'), 'FALHOU: 2º POST esperado created=false. Obtido: '.$this->httpResponseLine($r2));

        $count = \App\Models\AccessEvent::query()->where('source', 'catraca_bearer')->count();
        $this->assertSame(1, $count, 'FALHOU: esperada 1 linha em access_events (source=catraca_bearer). Contagem: '.$count);

        $deliveries = GestorAccessEventDelivery::query()->where('event_id', 'catraca-evt-unique-1')->count();
        $this->assertSame(2, $deliveries, 'Cada POST deve gerar linha de auditoria.');

        $this->reportStructuredTestOutcome(
            'Fluxo completo idempotência webhook catraca',
            'Garantir uma única linha em access_events após dois POSTs com o mesmo eventId; duas auditorias.',
            '1 linha em access_events; 1º created=true; 2º created=false; 2 deliveries.',
            'access_events: '.$count.' · deliveries: '.$deliveries,
            'EXITOSO',
        );
    }

    #[TestDox('Webhook catraca: carga leve — 35 eventIds distintos persistidos')]
    public function test_many_sequential_distinct_events_are_persisted(): void
    {
        $token = 'bulk-webhook-token';
        $this->gestorWithAccessTokenHash($token);

        $n = 35;
        $lastResponse = null;
        for ($i = 0; $i < $n; $i++) {
            $lastResponse = $this->withToken($token, 'Bearer')
                ->postJson(self::URL, [
                    'eventId' => 'bulk-evt-'.$i,
                    'type' => 'entry',
                    'aluno_id' => (string) (9000 + $i),
                ]);

            $this->assertSame(200, $lastResponse->getStatusCode(), "FALHOU na iteração {$i}/{$n}: esperado HTTP 200. ".$this->httpResponseLine($lastResponse));
            $this->assertTrue((bool) $lastResponse->json('ok'), "FALHOU na iteração {$i}/{$n}: esperado ok=true. ".$this->httpResponseLine($lastResponse));
            $this->assertTrue((bool) $lastResponse->json('created'), "FALHOU na iteração {$i}/{$n}: esperado created=true (evento novo). ".$this->httpResponseLine($lastResponse));
        }

        $count = \App\Models\AccessEvent::query()->where('source', 'catraca_bearer')->count();
        $this->assertSame($n, $count, 'FALHOU: esperadas '.$n.' linhas em access_events. Obtido: '.$count);

        $this->reportStructuredTestOutcome(
            'Carga sequencial: '.$n.' POSTs com eventIds distintos',
            'Validar que o webhook grava cada evento novo sem erro e que a contagem na base fecha.',
            $n.' respostas HTTP 200 com created=true; contagem na tabela access_events = '.$n.'.',
            'Última resposta (amostra): '.$this->httpResponseLine($lastResponse).' · Total na base: '.$count,
            'EXITOSO',
        );
    }
}
