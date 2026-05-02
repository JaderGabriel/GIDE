<?php

namespace Tests\Feature\Telas;

use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('telas-integracoes')]
class IntegrationsGuestRedirectTest extends TestCase
{
    public function test_guest_is_redirected_from_integrations_overview(): void
    {
        $this->get('/integracoes')->assertRedirect('/login');
    }
}
