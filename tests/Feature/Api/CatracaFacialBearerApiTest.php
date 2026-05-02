<?php

namespace Tests\Feature\Api;

use App\Models\Integration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('api-catraca')]
class CatracaFacialBearerApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_facial_nova_rejects_missing_bearer(): void
    {
        Integration::query()->create([
            'key' => 'catraca_frequencia',
            'name' => 'Catraca frequência',
            'enabled' => true,
            'auth_type' => 'bearer',
            'auth_token' => 'secret-bearer-token',
            'extra' => [],
        ]);

        $this->postJson('/api/v1/catraca-frequencia/gide/facial/nova', [
            'identificacao' => ['cod_aluno' => '211'],
            'external_id' => 'ext-1',
        ])->assertStatus(401);
    }

    public function test_facial_nova_accepts_valid_bearer(): void
    {
        $token = 'secret-bearer-token';
        Integration::query()->create([
            'key' => 'catraca_frequencia',
            'name' => 'Catraca frequência',
            'enabled' => true,
            'auth_type' => 'bearer',
            'auth_token' => $token,
            'extra' => [],
        ]);

        $this->withToken($token)
            ->postJson('/api/v1/catraca-frequencia/gide/facial/nova', [
                'meta' => ['event_id' => 'facial-evt-1'],
                'identificacao' => ['cod_aluno' => '211'],
                'external_id' => 'ext-211',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);
    }
}
