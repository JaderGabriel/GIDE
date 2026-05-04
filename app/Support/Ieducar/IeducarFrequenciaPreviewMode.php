<?php

namespace App\Support\Ieducar;

/**
 * Define {@see IeducarClient::postCatracaFrequenciaRegistro} `meta.preview` a partir do rótulo
 * gravado na integração Gestor (`extra.ieducar_processing.environment`), alinhado ao fluxo da UI.
 */
final class IeducarFrequenciaPreviewMode
{
    /**
     * @return bool true = meta.preview (simulação / não persistir conforme contrato i-Educar); false = gravação
     */
    public static function resolveMetaPreview(
        ?string $gestorIeducarProcessingEnvironment,
        bool $forcePreview,
        bool $forceApply,
    ): bool {
        if ($forceApply) {
            return false;
        }
        if ($forcePreview) {
            return true;
        }

        $env = strtolower(trim((string) ($gestorIeducarProcessingEnvironment ?? '')));

        return $env === 'preview';
    }

    public static function gestorEnvironmentLabel(?string $gestorIeducarProcessingEnvironment): string
    {
        $v = trim((string) ($gestorIeducarProcessingEnvironment ?? ''));

        return $v !== '' ? $v : '(não definido — tratado como homolog na regra de preview)';
    }

    /**
     * Texto curto para logs e Artisan.
     */
    public static function explain(bool $metaPreview, ?string $gestorEnv, bool $forced): string
    {
        if ($forced) {
            return $metaPreview
                ? 'meta.preview=true (override --force-preview)'
                : 'meta.preview=false (override --force-apply)';
        }

        $env = strtolower(trim((string) ($gestorEnv ?? '')));

        return $metaPreview
            ? 'meta.preview=true (Gestor extra.ieducar_processing.environment=preview)'
            : 'meta.preview=false (environment='.($env !== '' ? $env : 'homolog ou ausente').')';
    }
}
