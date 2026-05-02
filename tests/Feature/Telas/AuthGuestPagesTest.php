<?php

namespace Tests\Feature\Telas;

use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('telas-auth')]
class AuthGuestPagesTest extends TestCase
{
    public function test_guest_can_open_login_form(): void
    {
        $this->get('/login')->assertOk();
    }
}
