<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        if ($this->usesRefreshDatabase() && ! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('Extensão PHP pdo_sqlite ausente (SQLite em memória no phpunit.xml).');
        }

        parent::setUp();
    }

    protected function usesRefreshDatabase(): bool
    {
        return in_array(RefreshDatabase::class, class_uses_recursive(static::class), true);
    }

    /**
     * Escreve um bloco legível na saída (STDOUT) com o que foi exercitado.
     * Desative com TEST_STRUCTURED_OUTCOME=0 no phpunit.xml ou no ambiente.
     *
     * @param  'EXITOSO'|'FALHOU'|'IGNORADO'  $resultado
     */
    protected function reportStructuredTestOutcome(
        string $oqueSeTestou,
        string $objetivo,
        string $esperado,
        string $obtido,
        string $resultado = 'EXITOSO',
    ): void {
        if (! $this->structuredOutcomeEnabled()) {
            return;
        }

        $linhas = [
            '',
            '┌── Resumo do cenário ─────────────────────────────────────────',
            '│ O que se testou: '.$this->oneLine($oqueSeTestou),
            '│ Objetivo:        '.$this->oneLine($objetivo),
            '│ Esperado:        '.$this->oneLine($esperado),
            '│ Obtido:          '.$this->oneLine($obtido),
            '│ Resultado:       '.$resultado,
            '└──────────────────────────────────────────────────────────────',
            '',
        ];
        fwrite(STDOUT, implode("\n", $linhas));
    }

    protected function structuredOutcomeEnabled(): bool
    {
        $v = $_ENV['TEST_STRUCTURED_OUTCOME'] ?? getenv('TEST_STRUCTURED_OUTCOME');
        if ($v === false || $v === null || $v === '') {
            return true;
        }

        return $v !== '0' && $v !== 0;
    }

    protected function oneLine(string $s): string
    {
        $s = preg_replace('/\s+/u', ' ', $s) ?? $s;

        return mb_strlen($s) > 220 ? mb_substr($s, 0, 217).'…' : $s;
    }

    protected function httpResponseLine(TestResponse $response): string
    {
        $code = $response->getStatusCode();
        $ct = (string) $response->headers->get('Content-Type', '');
        $raw = (string) $response->getContent();
        $snippet = preg_replace('/\s+/u', ' ', $raw) ?? $raw;
        if (mb_strlen($snippet) > 360) {
            $snippet = mb_substr($snippet, 0, 357).'…';
        }

        return "HTTP {$code}; Content-Type: {$ct}; corpo: {$snippet}";
    }

    protected function htmlResponseLine(TestResponse $response): string
    {
        $code = $response->getStatusCode();
        $text = strip_tags((string) $response->getContent());
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        if (mb_strlen($text) > 320) {
            $text = mb_substr($text, 0, 317).'…';
        }

        return "HTTP {$code}; texto visível (resumo): {$text}";
    }

    /**
     * Garante código HTTP esperado e imprime relatório estruturado em caso de sucesso.
     * Em falha, imprime bloco FALHOU antes do assert para aparecer junto à mensagem PHPUnit.
     */
    protected function assertHttpStatusWithReport(
        TestResponse $response,
        int $expected,
        string $oqueSeTestou,
        string $objetivo,
        string $esperadoResumo,
    ): void {
        $actual = $response->getStatusCode();
        $obtido = $this->httpResponseLine($response);
        if ($actual !== $expected) {
            $this->reportStructuredTestOutcome(
                $oqueSeTestou,
                $objetivo,
                "HTTP {$expected} — {$esperadoResumo}",
                $obtido,
                'FALHOU',
            );
        }
        $this->assertSame(
            $expected,
            $actual,
            "FALHOU\nO que se testou: {$oqueSeTestou}\nObjetivo: {$objetivo}\nEsperado: HTTP {$expected} ({$esperadoResumo})\nObtido: {$obtido}",
        );
        $this->reportStructuredTestOutcome(
            $oqueSeTestou,
            $objetivo,
            "HTTP {$expected} — {$esperadoResumo}",
            $obtido,
            'EXITOSO',
        );
    }

    /**
     * @param  'EXITOSO'|'FALHOU'  $resultado
     */
    protected function assertHtmlStatusWithReport(
        TestResponse $response,
        int $expected,
        string $oqueSeTestou,
        string $objetivo,
        string $esperadoResumo,
    ): void {
        $actual = $response->getStatusCode();
        $obtido = $this->htmlResponseLine($response);
        if ($actual !== $expected) {
            $this->reportStructuredTestOutcome(
                $oqueSeTestou,
                $objetivo,
                "HTTP {$expected} — {$esperadoResumo}",
                $obtido,
                'FALHOU',
            );
        }
        $this->assertSame(
            $expected,
            $actual,
            "FALHOU\nO que se testou: {$oqueSeTestou}\nObjetivo: {$objetivo}\nEsperado: HTTP {$expected} ({$esperadoResumo})\nObtido: {$obtido}",
        );
        $this->reportStructuredTestOutcome(
            $oqueSeTestou,
            $objetivo,
            "HTTP {$expected} — {$esperadoResumo}",
            $obtido,
            'EXITOSO',
        );
    }
}
