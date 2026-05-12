<?php

namespace App\Services\Presence;

use App\Models\Integration;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class PresenceRuleEngine
{
    public const MODE_AUTO = 'auto';

    public const MODE_ALWAYS_MARK = 'always_mark';

    public const MODE_DISABLED = 'disabled';

    public const MODE_EXPLICIT_ONLY = 'explicit_only';

    public const VALID_MODES = [
        self::MODE_AUTO,
        self::MODE_ALWAYS_MARK,
        self::MODE_DISABLED,
        self::MODE_EXPLICIT_ONLY,
    ];

    /**
     * @return array{explicit_true: bool, explicit_false: bool}
     */
    private function payloadPresenceExplicitness(array $payload): array
    {
        $v = data_get($payload, 'action.mark_presence');
        if ($v === false || $v === 0 || $v === '0' || (is_string($v) && strtolower(trim($v)) === 'false')) {
            return ['explicit_true' => false, 'explicit_false' => true];
        }
        if (filter_var($v, FILTER_VALIDATE_BOOLEAN)) {
            return ['explicit_true' => true, 'explicit_false' => false];
        }

        $action = data_get($payload, 'action');
        if (is_string($action) && strtolower(trim($action)) === 'mark_presence') {
            return ['explicit_true' => true, 'explicit_false' => false];
        }
        if ($action === true) {
            return ['explicit_true' => true, 'explicit_false' => false];
        }

        return ['explicit_true' => false, 'explicit_false' => false];
    }

    /**
     * Resolve aluno_id e matricula_id do payload usando o mapa de campos.
     *
     * @return array{aluno_id: mixed, matricula_id: mixed, has_ids: bool}
     */
    private function resolveStudentIds(array $payload, array $payloadMap): array
    {
        $alunoIdKey = (string) ($payloadMap['aluno_id'] ?? 'aluno_id');
        $matriculaIdKey = (string) ($payloadMap['matricula_id'] ?? 'matricula_id');
        $alunoId = data_get($payload, $alunoIdKey);
        $matriculaId = data_get($payload, $matriculaIdKey);

        return [
            'aluno_id' => $alunoId,
            'matricula_id' => $matriculaId,
            'has_ids' => $alunoId !== null || $matriculaId !== null,
        ];
    }

    /**
     * Retorna uma análise com decisão e justificativa.
     *
     * Modos (extra.presence.mode):
     * - auto:           janelas + flag explícito (padrão)
     * - always_mark:    marca presença sempre que aluno identificado
     * - disabled:       nunca marca presença
     * - explicit_only:  só marca se action.mark_presence=true vier no payload
     */
    public function analyze(array $payload, ?CarbonInterface $occurredAt, Integration $ieducarIntegration): array
    {
        $presenceCfg = (array) data_get($ieducarIntegration->extra, 'presence', []);
        $payloadMap = (array) ($presenceCfg['payload_map'] ?? []);
        $mode = (string) ($presenceCfg['mode'] ?? self::MODE_AUTO);
        if (! in_array($mode, self::VALID_MODES, true)) {
            $mode = self::MODE_AUTO;
        }

        $eventTypeKey = (string) ($payloadMap['event_type'] ?? 'type');
        $eventType = data_get($payload, $eventTypeKey);

        if (($presenceCfg['ignore_exit_events'] ?? true) && is_string($eventType)) {
            $lower = strtolower($eventType);
            if (str_contains($lower, 'saida') || str_contains($lower, 'exit')) {
                return [
                    'action' => 'ignore',
                    'reason' => 'Evento de saída ignorado.',
                    'mode' => $mode,
                ];
            }
        }

        if ($mode === self::MODE_DISABLED) {
            return [
                'action' => 'ignore',
                'reason' => 'Motor de presença desabilitado (mode=disabled).',
                'mode' => $mode,
            ];
        }

        $explicit = $this->payloadPresenceExplicitness($payload);

        if ($explicit['explicit_false']) {
            return [
                'action' => 'ignore',
                'reason' => 'action.mark_presence=false declarado no payload.',
                'mode' => $mode,
            ];
        }

        if ($mode === self::MODE_ALWAYS_MARK) {
            return $this->tryMarkPresence($payload, $payloadMap, $presenceCfg, $mode,
                'Modo always_mark: presença marcada sem verificar janelas.');
        }

        if ($mode === self::MODE_EXPLICIT_ONLY) {
            if (! $explicit['explicit_true']) {
                return [
                    'action' => 'ignore',
                    'reason' => 'Modo explicit_only: action.mark_presence=true não declarado no payload.',
                    'mode' => $mode,
                ];
            }

            return $this->tryMarkPresence($payload, $payloadMap, $presenceCfg, $mode,
                'Modo explicit_only: action.mark_presence=true declarado.');
        }

        // --- MODE_AUTO (padrão) ---

        if ($explicit['explicit_true']) {
            return $this->tryMarkPresence($payload, $payloadMap, $presenceCfg, $mode,
                'Ação explícita de presença no payload.');
        }

        if (! $occurredAt) {
            return [
                'action' => 'ignore',
                'reason' => 'Sem occurred_at para aplicar janela.',
                'mode' => $mode,
            ];
        }

        $windows = $presenceCfg['windows'] ?? [];
        if (! is_array($windows) || count($windows) === 0) {
            return [
                'action' => 'ignore',
                'reason' => 'Sem janelas de presença configuradas.',
                'mode' => $mode,
            ];
        }

        $time = $occurredAt->format('H:i');
        $matchResult = $this->matchWindow($windows, $time);

        if (! $matchResult['matched']) {
            return [
                'action' => 'ignore',
                'reason' => $matchResult['reason'],
                'mode' => $mode,
                'time' => $time,
            ];
        }
        $matched = $matchResult['window'];

        $ids = $this->resolveStudentIds($payload, $payloadMap);
        if (! $ids['has_ids']) {
            return [
                'action' => 'ignore',
                'reason' => 'Sem aluno_id/matricula_id no payload.',
                'mode' => $mode,
            ];
        }

        return [
            'action' => 'mark_presence',
            'window' => $matched,
            'aluno_id' => $ids['aluno_id'],
            'matricula_id' => $ids['matricula_id'],
            'reason' => 'Dentro da janela configurada.',
            'mode' => $mode,
        ];
    }

    /**
     * Verifica se o horário cai em alguma janela, aplicando tolerance_minutes.
     *
     * tolerance_minutes expande a janela: start é antecipado, end é postergado.
     * Ex.: janela 07:00–09:30 com tolerance=15 → aceita 06:45–09:45.
     *
     * @return array{matched: bool, window: ?array, reason: string}
     */
    private function matchWindow(array $windows, string $time): array
    {
        $closestWindowName = null;
        $closestDiffMinutes = null;

        foreach ($windows as $w) {
            if (! is_array($w)) {
                continue;
            }
            $start = (string) ($w['start'] ?? '');
            $end = (string) ($w['end'] ?? '');
            if ($start === '' || $end === '') {
                continue;
            }

            $tolerance = max(0, (int) ($w['tolerance_minutes'] ?? 0));

            $refDate = '2000-01-01';
            $effectiveStart = Carbon::parse($refDate.' '.$start)->subMinutes($tolerance)->format('H:i');
            $effectiveEnd = Carbon::parse($refDate.' '.$end)->addMinutes($tolerance)->format('H:i');

            if ($time >= $effectiveStart && $time <= $effectiveEnd) {
                return [
                    'matched' => true,
                    'window' => $w,
                    'reason' => '',
                ];
            }

            $startMinutes = (int) Carbon::parse($refDate.' '.$effectiveStart)->diffInMinutes(Carbon::parse($refDate.' '.$time), false);
            $endMinutes = (int) Carbon::parse($refDate.' '.$time)->diffInMinutes(Carbon::parse($refDate.' '.$effectiveEnd), false);
            $dist = min(abs($startMinutes), abs($endMinutes));
            if ($closestDiffMinutes === null || $dist < $closestDiffMinutes) {
                $closestDiffMinutes = $dist;
                $closestWindowName = $w['name'] ?? ($start.'–'.$end);
            }
        }

        $hint = $closestWindowName !== null
            ? "Fora da janela de presença. Mais próxima: {$closestWindowName} ({$closestDiffMinutes}min de distância)."
            : 'Fora da janela de presença.';

        return ['matched' => false, 'window' => null, 'reason' => $hint];
    }

    /**
     * Tenta marcar presença — valida identificadores e monta retorno.
     */
    private function tryMarkPresence(array $payload, array $payloadMap, array $presenceCfg, string $mode, string $reason): array
    {
        $ids = $this->resolveStudentIds($payload, $payloadMap);
        if (! $ids['has_ids']) {
            return [
                'action' => 'ignore',
                'reason' => 'Sem aluno_id/matricula_id resolvíveis no payload.',
                'mode' => $mode,
            ];
        }

        $windows = $presenceCfg['windows'] ?? [];
        $windowMeta = ['name' => 'bypass', 'start' => '00:00', 'end' => '23:59'];
        if (is_array($windows) && isset($windows[0]) && is_array($windows[0])) {
            $windowMeta = $windows[0];
        }

        return [
            'action' => 'mark_presence',
            'window' => $windowMeta,
            'aluno_id' => $ids['aluno_id'],
            'matricula_id' => $ids['matricula_id'],
            'reason' => $reason,
            'mode' => $mode,
        ];
    }
}
