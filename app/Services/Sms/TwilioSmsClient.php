<?php

namespace App\Services\Sms;

use App\Models\Integration;
use App\Support\BrPhoneNormalizer;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Cliente REST da Twilio para SMS (API 2010-04-01).
 *
 * @see https://www.twilio.com/docs/sms/api/message-resource#create-a-message-resource
 */
class TwilioSmsClient
{
    public function __construct(private readonly Integration $integration) {}

    private function authToken(): string
    {
        $token = (string) ($this->integration->auth_token ?? '');
        if ($token === '') {
            throw new \RuntimeException('Auth Token Twilio não configurado (integrations.auth_token).');
        }

        return $token;
    }

    /**
     * Extrai o SID `AC`…32 hex a partir do valor gravado (evita colar URL ou `Accounts/AC…`).
     */
    public static function normalizeTwilioAccountSid(string $raw): string
    {
        $s = trim($raw);
        if ($s === '') {
            return '';
        }

        if (preg_match('/\b(AC[0-9a-f]{32})\b/i', $s, $m)) {
            return strtoupper($m[1]);
        }

        return $s;
    }

    private function accountSid(): string
    {
        $sid = self::normalizeTwilioAccountSid((string) data_get($this->integration->extra, 'account_sid', ''));
        if ($sid === '' || ! preg_match('/^AC[0-9a-f]{32}$/i', $sid)) {
            throw new \RuntimeException('Account SID Twilio não configurado ou inválido (esperado AC + 32 hex em integrations.extra.account_sid).');
        }

        return $sid;
    }

    /**
     * Raiz da API 2010-04-01 (sem barra final), alinhada ao envio real de mensagens.
     */
    public static function resolveApiRootFromIntegration(Integration $integration): string
    {
        $custom = rtrim((string) ($integration->base_url ?? ''), '/');
        if ($custom !== '' && str_contains(strtolower($custom), 'messages.json')) {
            $p = stripos($custom, '/accounts/');
            if ($p !== false) {
                return substr($custom, 0, $p);
            }
        }

        if ($custom !== '') {
            // Evita /Accounts/…/Accounts/… (base com conta ou variação de maiúsculas).
            $p = stripos($custom, '/accounts/');
            if ($p !== false) {
                return rtrim(substr($custom, 0, $p), '/');
            }

            return $custom;
        }

        return rtrim((string) config('integrations.sms.twilio_api_root', 'https://api.twilio.com/2010-04-01'), '/');
    }

    /**
     * URL do recurso Account (JSON) para testes de conectividade / credenciais.
     *
     * @see https://www.twilio.com/docs/iam/api/account
     */
    public static function accountJsonProbeUrl(Integration $integration): string
    {
        $sid = self::normalizeTwilioAccountSid((string) data_get($integration->extra, 'account_sid', ''));
        if ($sid === '' || ! preg_match('/^AC[0-9a-f]{32}$/i', $sid)) {
            throw new \RuntimeException('Account SID Twilio não configurado ou inválido (integrations.extra.account_sid).');
        }

        return self::resolveApiRootFromIntegration($integration).'/Accounts/'.$sid.'.json';
    }

    /**
     * URL completa do recurso Messages, ex.:
     * https://api.twilio.com/2010-04-01/Accounts/ACxxxx/Messages.json
     */
    private function messagesUrl(): string
    {
        $sid = $this->accountSid();

        return self::resolveApiRootFromIntegration($this->integration).'/Accounts/'.$sid.'/Messages.json';
    }

    /**
     * @param  string  $toDigits  E.164 só dígitos ou já com "+"
     * @param  string  $from  Número Twilio (E.164 com ou sem "+")
     * @param  string|null  $externalId  Reservado (paridade com {@see ZenviaSmsClient}; Twilio ignora)
     */
    public function sendText(string $toDigits, string $from, string $text, ?string $externalId = null): Response
    {
        $accountSid = $this->accountSid();
        $to = BrPhoneNormalizer::toE164Plus($toDigits);
        $fromNorm = BrPhoneNormalizer::toE164Plus($from);
        if ($to === '' || $fromNorm === '') {
            throw new \RuntimeException('To/From Twilio inválidos (use E.164, ex. +5538991758416).');
        }

        $payload = [
            'To' => $to,
            'From' => $fromNorm,
            'Body' => $text,
        ];

        return Http::timeout(30)
            ->withBasicAuth($accountSid, $this->authToken())
            ->asForm()
            ->acceptJson()
            ->post($this->messagesUrl(), $payload);
    }
}
