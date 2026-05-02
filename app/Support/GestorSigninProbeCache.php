<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Lembra o último resultado de Signin (GIDE → Gestor) para o mapa de integrações
 * refletir falhas 4xx/5xx ou indisponibilidade sem depender só de métricas locais.
 */
final class GestorSigninProbeCache
{
    private const CACHE_KEY = 'integration.gestor_signin_probe_v1';

    private const TTL_MINUTES = 45;

    public static function recordSuccess(): void
    {
        Cache::put(self::CACHE_KEY, [
            'ok' => true,
            'checked_at' => now()->toIso8601String(),
        ], now()->addMinutes(self::TTL_MINUTES));
    }

    public static function recordFailure(): void
    {
        Cache::put(self::CACHE_KEY, [
            'ok' => false,
            'checked_at' => now()->toIso8601String(),
        ], now()->addMinutes(self::TTL_MINUTES));
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public static function hasRecentFailure(): bool
    {
        $v = Cache::get(self::CACHE_KEY);

        return is_array($v) && ($v['ok'] ?? true) === false;
    }
}
