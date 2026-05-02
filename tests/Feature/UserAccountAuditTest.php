<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAccountAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_is_rejected_when_user_is_inactive(): void
    {
        $user = User::factory()->create([
            'username' => 'inativo_teste',
            'password' => 'senha-segura-1',
            'is_active' => false,
        ]);

        $response = $this->from('/login')->post('/login', [
            'username' => 'inativo_teste',
            'password' => 'senha-segura-1',
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertGuest();

        $this->assertDatabaseHas('user_audit_logs', [
            'user_id' => $user->id,
            'action' => 'login_denied_inactive',
        ]);
    }

    public function test_admin_can_open_user_audit_log_index(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin_audit',
            'is_admin' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get('/admin/auditoria-usuarios');

        $response->assertOk();
    }
}
