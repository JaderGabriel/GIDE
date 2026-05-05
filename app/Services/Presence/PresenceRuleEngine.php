<?php

namespace App\Services\Presence;

use App\Models\Integration;
use Carbon\CarbonInterface;

class PresenceRuleEngine
{
    /**
     * Regra:
     * - Se não vier nada: assume "permitido" e decide pelas janelas (comportamento padrão).
     * - Só impede presença se vier explicitamente false.
     * - Também aceita formatos legados para "forçar true".
     *
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

        // Compatibilidade: alguns emissores enviam action="mark_presence".
        $action = data_get($payload, 'action');
        if (is_string($action) && strtolower(trim($action)) === 'mark_presence') {
            return ['explicit_true' => true, 'explicit_false' => false];
        }

        // Compatibilidade: action=true (sem subchave).
        if ($action === true) {
            return ['explicit_true' => true, 'explicit_false' => false];
        }

        return ['explicit_true' => false, 'explicit_false' => false];
    }

    /**
     * Retorna uma análise com decisão e justificativa.
     *
     * Espera em integrations.extra.presence:
     * - windows: [{ name, start: "07:00", end: "09:30", tolerance_minutes: 15 }]
     * - ignore_exit_events: true
     * - payload_map: { aluno_id: "aluno_id", matricula_id: "matricula_id", event_type: "type", occurred_at: "occurred_at" }
     */
    public function analyze(array $payload, ?CarbonInterface $occurredAt, Integration $ieducarIntegration): array
    {
        $presenceCfg = (array) data_get($ieducarIntegration->extra, 'presence', []);
        $payloadMap = (array) ($presenceCfg['payload_map'] ?? []);

        $eventTypeKey = (string) ($payloadMap['event_type'] ?? 'type');
        $eventType = data_get($payload, $eventTypeKey);

        if (($presenceCfg['ignore_exit_events'] ?? true) && is_string($eventType)) {
            $lower = strtolower($eventType);
            if (str_contains($lower, 'saida') || str_contains($lower, 'exit')) {
                return [
                    'action' => 'ignore',
                    'reason' => 'Evento de saída ignorado.',
                ];
            }
        }

        $explicit = $this->payloadPresenceExplicitness($payload);

        if ($explicit['explicit_false']) {
            return [
                'action' => 'ignore',
                'reason' => 'action.mark_presence=false declarado no payload.',
            ];
        }

        if ($explicit['explicit_true']) {
            $alunoIdKey = (string) ($payloadMap['aluno_id'] ?? 'aluno_id');
            $matriculaIdKey = (string) ($payloadMap['matricula_id'] ?? 'matricula_id');
            $alunoId = data_get($payload, $alunoIdKey);
            $matriculaId = data_get($payload, $matriculaIdKey);
            if ($alunoId === null && $matriculaId === null) {
                return [
                    'action' => 'ignore',
                    'reason' => 'Ação explícita de presença sem aluno_id/matricula_id resolvíveis no payload.',
                ];
            }
            $windows = $presenceCfg['windows'] ?? [];
            $windowMeta = ['name' => 'explicit', 'start' => '00:00', 'end' => '23:59'];
            if (is_array($windows) && isset($windows[0]) && is_array($windows[0])) {
                $windowMeta = $windows[0];
            }

            return [
                'action' => 'mark_presence',
                'window' => $windowMeta,
                'aluno_id' => $alunoId,
                'matricula_id' => $matriculaId,
                'reason' => 'Ação explícita de presença no payload.',
            ];
        }

        if (! $occurredAt) {
            return [
                'action' => 'ignore',
                'reason' => 'Sem occurred_at para aplicar janela.',
            ];
        }

        $windows = $presenceCfg['windows'] ?? [];
        if (! is_array($windows) || count($windows) === 0) {
            return [
                'action' => 'ignore',
                'reason' => 'Sem janelas de presença configuradas.',
            ];
        }

        $time = $occurredAt->format('H:i');
        $matched = null;
        foreach ($windows as $w) {
            if (! is_array($w)) {
                continue;
            }
            $start = (string) ($w['start'] ?? '');
            $end = (string) ($w['end'] ?? '');
            if ($start === '' || $end === '') {
                continue;
            }
            if ($time >= $start && $time <= $end) {
                $matched = $w;
                break;
            }
        }

        if (! $matched) {
            return [
                'action' => 'ignore',
                'reason' => 'Fora da janela de presença.',
                'time' => $time,
            ];
        }

        $alunoIdKey = (string) ($payloadMap['aluno_id'] ?? 'aluno_id');
        $matriculaIdKey = (string) ($payloadMap['matricula_id'] ?? 'matricula_id');

        $alunoId = data_get($payload, $alunoIdKey);
        $matriculaId = data_get($payload, $matriculaIdKey);

        if ($alunoId === null && $matriculaId === null) {
            return [
                'action' => 'ignore',
                'reason' => 'Sem aluno_id/matricula_id no payload.',
            ];
        }

        return [
            'action' => 'mark_presence',
            'window' => $matched,
            'aluno_id' => $alunoId,
            'matricula_id' => $matriculaId,
            'reason' => 'Dentro da janela configurada.',
        ];
    }
}
