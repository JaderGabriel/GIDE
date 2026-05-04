<?php

namespace Tests\Feature\Api;

use App\Jobs\SendEnrollmentToAccessControl;
use App\Models\Integration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\HmacJsonRequest;
use Tests\TestCase;

#[Group('api-ieducar')]
class IeducarEnrollmentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_enrollment_endpoint_rejects_missing_hmac_headers(): void
    {
        Integration::query()->create([
            'key' => 'ieducar',
            'name' => 'iEducar',
            'enabled' => true,
            'auth_type' => 'none',
            'hmac_secret' => 'any-secret',
            'signature_ttl_seconds' => 3600,
            'extra' => [],
        ]);

        $this->postJson('/api/v1/ieducar/enrollments', ['aluno_id' => 1])
            ->assertStatus(401);
    }

    #[Group('fluxo-enrollment')]
    public function test_enrollment_endpoint_accepts_valid_hmac_and_dispatches_outbound_job_when_gestor_enabled(): void
    {
        $secret = 'test-hmac-secret-for-enrollment-32b';
        Integration::query()->create([
            'key' => 'ieducar',
            'name' => 'iEducar',
            'enabled' => true,
            'auth_type' => 'none',
            'hmac_secret' => $secret,
            'signature_ttl_seconds' => 3600,
            'extra' => [],
        ]);

        Integration::query()->create([
            'key' => 'gestor',
            'name' => 'Gestor',
            'enabled' => true,
            'auth_type' => 'none',
            'base_url' => 'https://gestor.test',
            'extra' => [
                'endpoints' => [
                    'enrollment_sync_path' => '/SDK/Invite',
                ],
                'defaults' => [
                    'unity_id' => 1,
                    'access_profile_id' => 2,
                ],
            ],
        ]);

        Queue::fake();

        $eventId = 'evt-enroll-'.uniqid('', true);
        $payload = ['aluno_id' => 211, 'matricula_id' => 9001];
        $signed = HmacJsonRequest::build($secret, $eventId, $payload);

        $this->withHeaders($signed['headers'])
            ->withBody($signed['body'], 'application/json')
            ->post('/api/v1/ieducar/enrollments')
            ->assertOk()
            ->assertJson(['ok' => true, 'created' => true]);

        Queue::assertPushed(SendEnrollmentToAccessControl::class, function (SendEnrollmentToAccessControl $job) use ($eventId): bool {
            return $job->eventId === $eventId;
        });
    }
}
