<?php

namespace Tests\Feature\Telas;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('telas-integracoes')]
class IntegrationsOverviewScreenTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_open_integrations_overview(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'is_active' => true]);

        $this->actingAs($user)->get('/integracoes')->assertOk();
    }

    public function test_integrations_status_json_returns_tone_and_segment_tones(): void
    {
        $user = User::factory()->create(['is_admin' => true, 'is_active' => true]);

        $this->actingAs($user)
            ->getJson('/integracoes/status')
            ->assertOk()
            ->assertJsonStructure(['tone', 'tones' => ['ieducar', 'gestor'], 'checked_at']);
    }
}
