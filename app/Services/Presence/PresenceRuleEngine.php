<?php

namespace App\Services\Presence;

use App\Models\Integration;
use Carbon\CarbonInterface;

class PresenceRuleEngine
{
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
