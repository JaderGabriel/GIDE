<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Exibição de datas no fuso da aplicação (padrão America/São_Paulo, GMT-3)
 * com texto relativo em português.
 */
final class DateDisplay
{
    public static function appTimezone(): string
    {
        return (string) config('app.timezone', 'America/Sao_Paulo');
    }

    public static function timezoneLabel(): string
    {
        try {
            $c = Carbon::now(self::appTimezone());
            if (self::appTimezone() === 'America/Sao_Paulo') {
                return 'GMT-3';
            }

            return $c->format('T');
        } catch (\Throwable) {
            return self::appTimezone();
        }
    }

    public static function carbon(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }
        if ($value instanceof CarbonInterface) {
            return Carbon::instance($value)->timezone(self::appTimezone());
        }
        if (is_int($value) || (is_string($value) && ctype_digit((string) $value))) {
            return Carbon::createFromTimestamp((int) $value, self::appTimezone());
        }
        if (is_string($value)) {
            try {
                return Carbon::parse($value)->timezone(self::appTimezone());
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }

    public static function formatHumanFromUnix(int $timestamp, bool $withRelative = true): string
    {
        if ($timestamp <= 0) {
            return '—';
        }

        return self::formatHuman(Carbon::createFromTimestamp($timestamp, self::appTimezone()), $withRelative);
    }

    /**
     * Ex.: 1 de maio de 2026, 14:32 — há 2 minutos · GMT-3
     */
    public static function formatHuman(?CarbonInterface $moment, bool $withRelative = true): string
    {
        if (! $moment) {
            return '—';
        }

        $c = Carbon::instance($moment)->timezone(self::appTimezone())->locale('pt_BR');
        // isoFormat respeita MMMM em português com mais consistência que translatedFormat em alguns ambientes.
        $main = $c->isoFormat('D [de] MMMM [de] YYYY, HH:mm');
        $tz = self::timezoneLabel();
        if (! $withRelative) {
            return $main.' · '.$tz;
        }

        $now = Carbon::now(self::appTimezone())->locale('pt_BR');
        $rel = $c->diffInSeconds($now) < 2
            ? 'agora'
            : $c->locale('pt_BR')->diffForHumans();

        return $main.' — '.$rel.' · '.$tz;
    }

    /** Uma linha curta para saída CLI (comandos agendados / artisan). */
    public static function cliReferenceLine(): string
    {
        $c = Carbon::now(self::appTimezone())->locale('pt_BR');

        return 'Referência: '.$c->isoFormat('D [de] MMMM [de] YYYY, HH:mm:ss').' — '.$c->diffForHumans().' · '.self::timezoneLabel();
    }

    /**
     * Exibição compacta de data_ref (payload) em listagens: DD/MM/YYYY HH:MM no fuso da app.
     */
    public static function formatDataRefTable(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }
        $c = self::carbon(is_string($value) ? $value : (string) $value);
        if ($c === null) {
            return is_scalar($value) ? (string) $value : '—';
        }

        return $c->format('d/m/Y H:i');
    }
}
