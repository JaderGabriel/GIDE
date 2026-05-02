<?php

namespace Tests\Feature\Telas;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('telas-dashboard')]
class DashboardScreenTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_is_redirected_from_dashboard_to_integrations(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'is_active' => true]);

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('integrations.overview'));
    }

    public function test_admin_can_open_dashboard(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'is_active' => true]);

        $this->actingAs($admin)->get('/dashboard')->assertOk();
    }
}
