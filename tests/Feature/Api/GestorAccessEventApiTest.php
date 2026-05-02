<?php

namespace Tests\Feature\Api;

use App\Models\Integration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\HmacJsonRequest;
use Tests\TestCase;

#[Group('api-gestor')]
class GestorAccessEventApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_access_events_rejects_invalid_hmac(): void
    {
        Integration::query()->create([
            'key' => 'gestor',
            'name' => 'Gestor',
            'enabled' => true,
            'auth_type' => 'none',
            'hmac_secret' => 'gestor-hmac-secret-32bytes-long-string',
            'signature_ttl_seconds' => 3600,
            'extra' => [],
        ]);

        $this->postJson('/api/v1/gestor/access-events', ['occurred_at' => now()->toIso8601String()], [
            'X-Event-Id' => 'evt-1',
            'X-Timestamp' => (string) time(),
            'X-Signature' => 'invalid',
        ])->assertStatus(401);
    }

    public function test_access_events_accepts_valid_hmac(): void
    {
        $secret = 'gestor-hmac-secret-32bytes-long-string';
        Integration::query()->create([
            'key' => 'gestor',
            'name' => 'Gestor',
            'enabled' => true,
            'auth_type' => 'none',
            'hmac_secret' => $secret,
            'signature_ttl_seconds' => 3600,
            'extra' => [],
        ]);

        $eventId = 'evt-gestor-'.uniqid('', true);
        $payload = ['occurred_at' => now()->toIso8601String(), 'aluno_id' => 211];
        $signed = HmacJsonRequest::build($secret, $eventId, $payload);

        $this->withHeaders($signed['headers'])
            ->withBody($signed['body'], 'application/json')
            ->post('/api/v1/gestor/access-events')
            ->assertOk()
            ->assertJson(['ok' => true, 'created' => true]);

        $this->assertDatabaseHas('access_events', [
            'source' => 'gestor',
            'event_id' => $eventId,
        ]);
    }
}
