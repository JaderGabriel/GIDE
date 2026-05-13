<?php

namespace App\Console\Commands;

use App\Models\Integration;
use App\Services\Sms\TwilioSmsClient;
use App\Support\BrPhoneNormalizer;
use Illuminate\Console\Command;
use Illuminate\Http\Client\Response;

/**
 * Teste de envio SMS via Twilio usando apenas a integração `integrations.key=sms` no banco.
 *
 * @see https://www.twilio.com/docs/sms/api/message-resource#create-a-message-resource
 */
class SmsTwilioTestCommand extends Command
{
    protected $signature = 'sms:twilio-test
                            {--to=+5538991758416 : Número destino em E.164}
                            {--body= : Corpo da mensagem (omissão: texto com timestamp)}';

    protected $description = 'Envia um SMS de teste pela API REST da Twilio (credenciais em integrations key=sms).';

    public function handle(): int
    {
        $toRaw = trim((string) $this->option('to'));
        $body = trim((string) $this->option('body'));
        if ($body === '') {
            $body = 'GIDE sms:twilio-test · '.now()->toIso8601String();
        }

        try {
            return $this->sendViaIntegration($toRaw, $body);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }

    private function sendViaIntegration(string $toRaw, string $body): int
    {
        $integration = Integration::query()->where('key', 'sms')->first();
        if (! $integration) {
            $this->error('Integração SMS não encontrada (key=sms). Configure em /integracoes/sms.');

            return self::FAILURE;
        }

        if ((string) data_get($integration->extra, 'provider', 'twilio') !== 'twilio') {
            $this->error('A integração SMS não está com provider=twilio.');

            return self::FAILURE;
        }

        $from = (string) data_get($integration->extra, 'from', '');
        if ($from === '') {
            $this->error('Campo From não configurado na integração SMS (/integracoes/sms).');

            return self::FAILURE;
        }

        $toDigits = BrPhoneNormalizer::toE164Digits($toRaw);
        if ($toDigits === '') {
            $this->error('Número --to inválido (use E.164, ex. +5538991758416).');

            return self::FAILURE;
        }

        $this->comment('Integração SMS #'.$integration->id.' (Twilio, credenciais no banco)');

        $resp = (new TwilioSmsClient($integration))->sendText($toDigits, $from, $body, null);

        return $this->printResponse($resp);
    }

    private function printResponse(Response $resp): int
    {
        $this->info('HTTP '.$resp->status());
        $json = $resp->json();
        if (is_array($json)) {
            $this->line(json_encode($json, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?: '{}');
        } else {
            $this->line(mb_substr((string) $resp->body(), 0, 4000));
        }

        return $resp->successful() ? self::SUCCESS : self::FAILURE;
    }
}
