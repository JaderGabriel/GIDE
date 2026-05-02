<?php

namespace Tests\Feature\Telas;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('telas-users')]
class UsersManagementScreenTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_users(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'is_active' => true]);

        $this->actingAs($admin)->get('/usuarios')->assertOk();
    }

    public function test_non_admin_cannot_list_users(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'is_active' => true]);

        $this->actingAs($user)->get('/usuarios')->assertForbidden();
    }

    public function test_admin_can_open_create_user_form(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'is_active' => true]);

        $this->actingAs($admin)->get('/usuarios/novo')->assertOk();
    }

    public function test_admin_can_create_user(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'is_active' => true]);

        $response = $this->actingAs($admin)->post('/usuarios', [
            'name' => 'Novo Operador',
            'username' => 'novo_op',
            'email' => 'novo_op@example.test',
            'password' => 'senha-segura-1',
            'password_confirmation' => 'senha-segura-1',
        ]);

        $response->assertRedirect('/usuarios');
        $this->assertDatabaseHas('users', ['username' => 'novo_op', 'email' => 'novo_op@example.test']);
    }

    public function test_admin_cannot_deactivate_self_via_policy(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'is_active' => true]);

        $this->actingAs($admin)
            ->from('/usuarios')
            ->post(route('users.deactivate', $admin))
            ->assertSessionHasErrors('user');
    }
}
