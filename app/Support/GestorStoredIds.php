<?php

namespace App\Support;

/**
 * IDs numéricos do Gestor (unity, access profile) persistidos em integrations.extra.
 * Zero ou vazio equivale a não configurado (null na lógica).
 */
final class GestorStoredIds
{
    public static function positiveIntOrNull(mixed $raw): ?int
    {
        if ($raw === null) {
            return null;
        }
        if (is_string($raw) && trim($raw) === '') {
            return null;
        }
        $n = (int) $raw;

        return $n > 0 ? $n : null;
    }

    public static function stringForNumericInput(mixed $raw): string
    {
        $n = self::positiveIntOrNull($raw);

        return $n !== null ? (string) $n : '';
    }
}
