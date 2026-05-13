<?php

namespace Tests\Unit;

use App\Models\GestorAccessEventDelivery;
use App\Support\Presence\AccessEventOccurredAtResolver;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

class AccessEventOccurredAtResolverTest extends TestCase
{
    #[TestDox('Catraca: creationDate sem offset → interpreta UTC e converte para APP_TIMEZONE')]
    public function test_catraca_creation_date_without_offset_is_interpreted_as_utc(): void
    {
        $appTz = 'America/Sao_Paulo';
        config(['app.timezone' => $appTz]);

        $out = AccessEventOccurredAtResolver::resolve(
            ['creationDate' => '2026-06-15T18:30:00'],
            GestorAccessEventDelivery::CHANNEL_CATRACA_BEARER,
        );

        $this->assertTrue($out['interpreted_as_utc']);
        $this->assertFalse($out['tz_declared']);
        $this->assertSame('+00:00', $out['original_tz']);
        $expected = Carbon::parse('2026-06-15T18:30:00', 'UTC')->timezone($appTz);
        $this->assertTrue($out['occurred_at']?->equalTo($expected));
        $this->assertSame($expected->toIso8601String(), $out['normalized']);
    }

    #[TestDox('Catraca: creationDate com Z mantém instante explícito')]
    public function test_catraca_creation_date_with_z_does_not_set_implicit_utc_flag(): void
    {
        config(['app.timezone' => 'America/Sao_Paulo']);

        $out = AccessEventOccurredAtResolver::resolve(
            ['creationDate' => '2026-06-15T18:30:00Z'],
            GestorAccessEventDelivery::CHANNEL_CATRACA_BEARER,
        );

        $this->assertFalse($out['interpreted_as_utc']);
        $this->assertTrue($out['tz_declared']);
        $this->assertSame('+00:00', $out['original_tz']);
    }

    #[TestDox('Gestor HMAC: sem offset continua na timezone da aplicação (não assume UTC)')]
    public function test_gestor_channel_without_offset_uses_app_timezone_not_utc_assumption(): void
    {
        $appTz = 'America/Sao_Paulo';
        config(['app.timezone' => $appTz]);

        $out = AccessEventOccurredAtResolver::resolve(
            ['creationDate' => '2026-06-15T18:30:00'],
            GestorAccessEventDelivery::CHANNEL_GESTOR_HMAC,
        );

        $this->assertFalse($out['interpreted_as_utc']);
        $this->assertFalse($out['tz_declared']);
        $this->assertNull($out['original_tz']);
        $expected = Carbon::parse('2026-06-15T18:30:00', $appTz);
        $this->assertTrue($out['occurred_at']?->equalTo($expected));
    }
}
