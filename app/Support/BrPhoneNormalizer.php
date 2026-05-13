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
     * E.164 com prefixo "+" (ex.: +5538991758416), para APIs como Twilio.
     */
    public static function toE164Plus(string $input): string
    {
        $t = trim($input);
        if ($t === '') {
            return '';
        }
        if (str_starts_with($t, '+')) {
            $digits = preg_replace('/\D+/', '', substr($t, 1)) ?? '';

            return $digits !== '' ? '+'.$digits : '';
        }

        $digits = self::toE164Digits($t);

        return $digits !== '' ? '+'.$digits : '';
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
