<?php

namespace App\Support;

use App\Models\Integration;

/**
 * Rótulo de ambiente iEducar (preview vs homologação) na integração Gestor.
 * Janelas e API do Diário vêm da integração iEducar; o valor em extra só registra o ambiente declarado.
 */
final class GestorIeducarProcessing
{
    public const ENV_PREVIEW = 'preview';

    public const ENV_HOMOLOG = 'homolog';

    public static function environmentLabel(Integration $gestor): string
    {
        $env = (string) data_get($gestor->extra, 'ieducar_processing.environment', self::ENV_HOMOLOG);

        return $env === self::ENV_PREVIEW ? 'preview' : 'homologação';
    }
}
