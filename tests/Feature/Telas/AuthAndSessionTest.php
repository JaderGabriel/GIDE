<?php

namespace Tests\Feature\Telas;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('telas-auth')]
class AuthAndSessionTest extends TestCase
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

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'is_active' => true]);

        $this->actingAs($user)->post('/logout')->assertRedirect('/');

        $this->assertGuest();
    }
}
