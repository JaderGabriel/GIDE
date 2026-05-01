<?php

namespace App\Services\Sms;

use App\Models\Integration;
use Illuminate\Support\Facades\Http;

class ZenviaSmsClient
{
    public function __construct(private readonly Integration $integration) {}

    private function apiToken(): string
    {
        $token = (string) ($this->integration->auth_token ?? '');
        if ($token === '') {
            throw new \RuntimeException('Token da API SMS não configurado (integrations.auth_token).');
        }

        return $token;
    }

    private function baseUrl(): string
    {
        $base = rtrim((string) ($this->integration->base_url ?? ''), '/');
        if ($base !== '') {
            return $base;
        }

        return rtrim((string) config('integrations.sms.default_base_url'), '/');
    }

    public function sendText(string $to, string $from, string $text, ?string $externalId = null)
    {
        // Doc: POST /channels/sms/messages com header X-API-TOKEN
        $payload = [
            'from' => $from,
            'to' => $to,
            'contents' => [
                ['type' => 'text', 'text' => $text],
            ],
        ];

        if ($externalId) {
            $payload['externalId'] = $externalId;
        }

        return Http::timeout(30)
            ->withHeaders([
                'X-API-TOKEN' => $this->apiToken(),
                'Accept' => 'application/json',
            ])
            ->post($this->baseUrl().'/channels/sms/messages', $payload);
    }
}
