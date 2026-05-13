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
    public function account_json_probe_url_inclui_sid(): void
    {
        $i = new Integration([
            'base_url' => '',
            'extra' => ['account_sid' => 'ACaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'],
        ]);

        $url = TwilioSmsClient::accountJsonProbeUrl($i);
        $this->assertStringContainsString('/Accounts/ACaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa.json', $url);
    }
}
