<?php

namespace Tests\Support;

use App\Http\Middleware\VerifyHmacSignature;

/**
 * Cabeçalhos HMAC alinhados a {@see VerifyHmacSignature}.
 */
final class HmacJsonRequest
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array{body: string, headers: array<string, string>}
     */
    public static function build(string $hmacSecretPlain, string $eventId, array $payload): array
    {
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $timestamp = (string) time();
        $signed = $timestamp.'.'.$eventId.'.'.$body;
        $signature = hash_hmac('sha256', $signed, $hmacSecretPlain);

        return [
            'body' => $body,
            'headers' => [
                'X-Event-Id' => $eventId,
                'X-Timestamp' => $timestamp,
                'X-Signature' => $signature,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ];
    }
}
