<?php

namespace App\Support;

use App\Models\Integration;
use Illuminate\Support\Facades\Hash;

/**
 * Token de acesso do webhook {@see \App\Http\Controllers\Api\CatracaAccessWebhookController}:
 * um único hash em {@see Integration::$extra} (legado: {@see self::LEGACY_HASH_KEY}).
 */
final class GestorCatracaAccessToken
{
    public const HASH_KEY = 'catraca_access_token_hash';

    public const CREATED_AT_KEY = 'catraca_access_token_created_at';

    /** @deprecated Prefer {@see self::HASH_KEY}; ainda aceite no middleware para instalações antigas. */
    public const LEGACY_HASH_KEY = 'catraca_webhook_bearer_hash';

    public static function storedHash(Integration $integration): string
    {
        $extra = (array) ($integration->extra ?? []);

        return (string) ($extra[self::HASH_KEY] ?? $extra[self::LEGACY_HASH_KEY] ?? '');
    }

    public static function isConfigured(Integration $integration): bool
    {
        return self::storedHash($integration) !== '';
    }

    public static function checkPlainAgainstIntegration(string $plainToken, Integration $integration): bool
    {
        $hash = self::storedHash($integration);

        return $hash !== '' && Hash::check($plainToken, $hash);
    }
}
