<?php

namespace Tests\Feature\Telas;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('telas-auditoria')]
class AuditLogScreenTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_user_audit_log_index(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin_audit',
            'is_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin)->get('/admin/auditoria-usuarios')->assertOk();
    }

    public function test_non_admin_cannot_open_audit_log_index(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'is_active' => true]);

        $this->actingAs($user)->get('/admin/auditoria-usuarios')->assertForbidden();
    }
}
