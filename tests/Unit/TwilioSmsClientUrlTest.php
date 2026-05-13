<?php

namespace Tests\Unit;

use App\Models\Integration;
use App\Services\Sms\TwilioSmsClient;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TwilioSmsClientUrlTest extends TestCase
{
    #[Test]
    public function resolve_api_root_usa_config_quando_base_url_vazia(): void
    {
        $i = new Integration([
            'base_url' => '',
            'extra' => ['account_sid' => 'ACaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'],
        ]);

        $root = TwilioSmsClient::resolveApiRootFromIntegration($i);
        $this->assertStringEndsWith('/2010-04-01', $root);
        $this->assertStringStartsWith('https://', $root);
    }

    #[Test]
    public function resolve_api_root_remove_segmento_accounts_quando_base_inclui_sid(): void
    {
        $i = new Integration([
            'base_url' => 'https://api.twilio.com/2010-04-01/Accounts/ACaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
            'extra' => ['account_sid' => 'ACaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'],
        ]);

        $root = TwilioSmsClient::resolveApiRootFromIntegration($i);
        $this->assertSame('https://api.twilio.com/2010-04-01', $root);

        $messages = 'https://api.twilio.com/2010-04-01/Accounts/ACaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa/Messages.json';
        $i2 = new Integration([
            'base_url' => $messages,
            'extra' => ['account_sid' => 'ACaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'],
        ]);
        $this->assertSame('https://api.twilio.com/2010-04-01', TwilioSmsClient::resolveApiRootFromIntegration($i2));
    }

    #[Test]
    public function resolve_api_root_funciona_com_accounts_em_minusculas(): void
    {
        $i = new Integration([
            'base_url' => 'https://api.twilio.com/2010-04-01/accounts/ACaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
            'extra' => ['account_sid' => 'ACaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'],
        ]);

        $this->assertSame('https://api.twilio.com/2010-04-01', TwilioSmsClient::resolveApiRootFromIntegration($i));
    }

    #[Test]
    public function normalize_account_sid_extrai_de_url(): void
    {
        $this->assertSame(
            'ACAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA',
            TwilioSmsClient::normalizeTwilioAccountSid(
                'https://api.twilio.com/2010-04-01/Accounts/ACaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa/Messages.json',
            ),
        );
        $this->assertSame('ACAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA', TwilioSmsClient::normalizeTwilioAccountSid('Accounts/ACaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'));
    }

    #[Test]
    public function account_json_probe_url_inclui_sid(): void
    {
        $i = new Integration([
            'base_url' => '',
            'extra' => ['account_sid' => 'ACaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'],
        ]);

        $url = TwilioSmsClient::accountJsonProbeUrl($i);
        $this->assertStringContainsString('/Accounts/ACAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA.json', $url);
        $this->assertStringNotContainsString('/Accounts/Accounts/', $url);
    }
}
