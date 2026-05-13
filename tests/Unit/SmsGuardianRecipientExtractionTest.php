<?php

namespace Tests\Unit;

use App\Services\Sms\SmsService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SmsGuardianRecipientExtractionTest extends TestCase
{
    #[Test]
    public function extrai_telefones_de_chaves_escalares_e_responsavel(): void
    {
        $digits = SmsService::extractGuardianRecipientDigitsFromPayload([
            'phone' => '(11) 98888-7777',
            'responsavel' => ['telefone' => '11977776666'],
        ]);

        $this->assertContains('5511988887777', $digits);
        $this->assertContains('5511977776666', $digits);
    }

    #[Test]
    public function extrai_telefones_de_lista_responsaveis(): void
    {
        $digits = SmsService::extractGuardianRecipientDigitsFromPayload([
            'responsaveis' => [
                ['nome' => 'A', 'celular' => '+55 11 90000-1111'],
                ['telefone' => '5511900002222'],
            ],
        ]);

        $this->assertContains('5511900001111', $digits);
        $this->assertContains('5511900002222', $digits);
    }
}
