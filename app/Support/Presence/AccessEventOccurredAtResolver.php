<?php

namespace App\Support\Presence;

use App\Models\GestorAccessEventDelivery;
use Carbon\Carbon;
use Throwable;

/**
 * Normaliza {@see GestorAccessEventWebhookService} / admin: data do evento a partir do payload.
 *
 * Para o canal {@see GestorAccessEventDelivery::CHANNEL_CATRACA_BEARER}, se a string de data/hora
 * não traz fuso explícito (Z ou offset numérico), assume-se que o equipamento enviou em **UTC**
 * e converte-se para {@see config('app.timezone')}. Outros canais mantêm o comportamento anterior
 * (interpretação na timezone da aplicação quando não há offset).
 */
final class AccessEventOccurredAtResolver
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array{
     *     occurred_at: ?Carbon,
     *     raw: ?string,
     *     original_tz: ?string,
     *     tz_declared: bool,
     *     normalized: ?string,
     *     interpreted_as_utc: bool,
     * }
     */
    public static function resolve(array $payload, ?string $inboundChannel = null): array
    {
        $candidateTs = data_get($payload, 'occurred_at')
            ?? data_get($payload, 'timestamp')
            ?? data_get($payload, 'event_time')
            ?? data_get($payload, 'creationDate')
            ?? data_get($payload, 'creation_date');

        $empty = [
            'occurred_at' => null,
            'raw' => null,
            'original_tz' => null,
            'tz_declared' => false,
            'normalized' => null,
            'interpreted_as_utc' => false,
        ];

        if (! is_string($candidateTs) || trim($candidateTs) === '') {
            return $empty;
        }

        $candidateTs = trim($candidateTs);

        $tzDeclared = (bool) preg_match('/[Zz]$|[+\-]\d{2}:\d{2}$|[+\-]\d{4}$/', $candidateTs);

        $appTz = (string) config('app.timezone', 'America/Sao_Paulo');
        $implicitUtcCatraca = $inboundChannel === GestorAccessEventDelivery::CHANNEL_CATRACA_BEARER;

        try {
            if ($tzDeclared) {
                $parsed = Carbon::parse($candidateTs);
                $originalTz = $parsed->format('P');
                $normalized = $parsed->copy()->timezone($appTz);
                $interpretedAsUtc = false;
            } elseif ($implicitUtcCatraca) {
                $parsed = Carbon::parse($candidateTs, 'UTC');
                $originalTz = '+00:00';
                $normalized = $parsed->copy()->timezone($appTz);
                $interpretedAsUtc = true;
            } else {
                $parsed = Carbon::parse($candidateTs);
                $originalTz = null;
                $normalized = $parsed->copy()->timezone($appTz);
                $interpretedAsUtc = false;
            }
        } catch (Throwable) {
            return array_merge($empty, ['raw' => $candidateTs]);
        }

        return [
            'occurred_at' => $normalized,
            'raw' => $candidateTs,
            'original_tz' => $originalTz,
            'tz_declared' => $tzDeclared,
            'normalized' => $normalized->toIso8601String(),
            'interpreted_as_utc' => $interpretedAsUtc,
        ];
    }
}
