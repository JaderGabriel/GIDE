<?php

namespace App\Support;

use App\Models\Integration;

/**
 * Ambiente iEducar usado ao processar eventos recebidos do Gestor (ex.: lançamento via PresenceMarker).
 * Configuração em integrations(key=gestor).extra.ieducar_processing.
 */
final class GestorIeducarProcessing
{
    public const ENV_PREVIEW = 'preview';

    public const ENV_HOMOLOG = 'homolog';

    /**
     * Clona a integração iEducar aplicando base_url/access_key do bucket preview ou homolog quando informados.
     * Se o bucket escolhido estiver vazio, usa a integração iEducar principal sem alterações.
     */
    public static function resolveApiIntegrationForGestorInbound(Integration $gestor, Integration $ieducar): Integration
    {
        $env = (string) data_get($gestor->extra, 'ieducar_processing.environment', self::ENV_HOMOLOG);
        if ($env !== self::ENV_PREVIEW && $env !== self::ENV_HOMOLOG) {
            $env = self::ENV_HOMOLOG;
        }

        $bucket = $env === self::ENV_PREVIEW ? self::ENV_PREVIEW : self::ENV_HOMOLOG;
        $over = (array) data_get($gestor->extra, 'ieducar_processing.'.$bucket, []);

        $baseUrl = trim((string) ($over['base_url'] ?? ''));
        $accessKey = trim((string) ($over['access_key'] ?? ''));

        if ($baseUrl === '' && $accessKey === '') {
            return $ieducar;
        }

        $clone = $ieducar->replicate();
        if ($baseUrl !== '') {
            $clone->base_url = $baseUrl;
        }

        $extra = (array) ($ieducar->extra ?? []);
        if ($accessKey !== '') {
            $extra['access_key'] = $accessKey;
        }
        $clone->extra = $extra;

        return $clone;
    }
}
