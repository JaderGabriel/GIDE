<?php

namespace App\Support;

final class BrPhoneNormalizer
{
    /**
     * Normaliza para dígitos E.164 sem "+" (ex.: 5511988887777).
     */
    public static function toE164Digits(string $input): string
    {
        $digits = preg_replace('/\D+/', '', $input) ?? '';
        if ($digits === '') {
            return '';
        }

        $digits = ltrim($digits, '0');

        if (strlen($digits) === 10 || strlen($digits) === 11) {
            $digits = '55'.$digits;
        }

        if (strlen($digits) < 12) {
            return '';
        }

        return $digits;
    }

    /**
     * @return list<string>
     */
    public static function parseLinesToE164(string $multiline): array
    {
        $out = [];
        foreach (preg_split('/\r\n|\r|\n/', $multiline) ?: [] as $line) {
            $n = self::toE164Digits(trim((string) $line));
            if ($n !== '') {
                $out[] = $n;
            }
        }

        return array_values(array_unique($out));
    }
}
