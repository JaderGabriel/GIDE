<?php

namespace App\Services\Timeline;

use App\Models\FacialGestorCatracaHistory;
use App\Models\GestorAccessEventDelivery;
use App\Models\SmsDelivery;
use App\Models\StudentEnrichmentCache;
use Illuminate\Support\Collection;

class StudentTimelineService
{
    /**
     * Agrega eventos de múltiplas tabelas para um aluno, ordenados cronologicamente.
     *
     * @return Collection<int, array{type: string, at: string, summary: string, detail_url: string|null, data: array}>
     */
    public function getTimeline(int $codAluno, int $limit = 50): Collection
    {
        $events = collect();

        $this->appendAccessEvents($events, $codAluno, $limit);
        $this->appendSmsEvents($events, $codAluno, $limit);
        $this->appendFacialEvents($events, $codAluno, $limit);

        return $events
            ->sortByDesc('at')
            ->take($limit)
            ->values();
    }

    /**
     * Retorna dados cacheados do aluno (enriquecimento).
     *
     * @return array<string, mixed>|null
     */
    public function getStudentData(int $codAluno): ?array
    {
        $cache = StudentEnrichmentCache::query()
            ->where('cod_aluno', $codAluno)
            ->first();

        return $cache?->data;
    }

    /**
     * Retorna os últimos N alunos distintos com access-events recentes.
     *
     * @return Collection<int, array{cod_aluno: int, access_count: int, last_event_at: string, delivery_id: int}>
     */
    public function getRecentActiveStudents(int $limit = 10): Collection
    {
        $deliveries = GestorAccessEventDelivery::query()
            ->whereNotNull('analysis_json')
            ->latest()
            ->limit($limit * 5)
            ->get();

        $seen = [];
        $results = [];

        foreach ($deliveries as $delivery) {
            $codAluno = $this->extractCodAluno($delivery);
            if ($codAluno < 1 || isset($seen[$codAluno])) {
                continue;
            }
            $seen[$codAluno] = true;

            $results[] = [
                'cod_aluno' => $codAluno,
                'access_count' => $this->countAccessDeliveriesForAluno($codAluno),
                'last_event_at' => $delivery->created_at?->toIso8601String() ?? '',
                'delivery_id' => $delivery->id,
            ];

            if (count($results) >= $limit) {
                break;
            }
        }

        return collect($results);
    }

    private function countAccessDeliveriesForAluno(int $codAluno): int
    {
        return GestorAccessEventDelivery::query()
            ->whereNotNull('analysis_json')
            ->get()
            ->filter(fn (GestorAccessEventDelivery $d) => $this->codAlunoFromDelivery($d) === $codAluno)
            ->count();
    }

    private function appendAccessEvents(Collection &$events, int $codAluno, int $limit): void
    {
        $deliveries = GestorAccessEventDelivery::query()
            ->with('accessEvent')
            ->whereNotNull('analysis_json')
            ->latest('id')
            ->limit($limit * 3)
            ->get();

        foreach ($deliveries as $delivery) {
            if ($this->codAlunoFromDelivery($delivery) !== $codAluno) {
                continue;
            }

            $analysis = is_array($delivery->analysis_json) ? $delivery->analysis_json : [];
            $payload = is_array($delivery->inbound_payload) ? $delivery->inbound_payload : [];
            $action = (string) data_get($analysis, 'action', 'unknown');
            $status = (string) $delivery->processing_status;
            $way = data_get($payload, 'way');
            $wayStr = is_string($way) ? $way : null;
            $accessPath = data_get($analysis, 'access_path');
            $accessWay = data_get($analysis, 'access_way');

            $atIso = $delivery->accessEvent?->occurred_at?->toIso8601String()
                ?? (string) data_get($analysis, 'timestamp_info.normalized_br')
                ?: ($delivery->created_at?->toIso8601String() ?? '');

            $timelineFlow = $this->resolveTimelineFlow($action, $accessPath);
            $tooltip = $this->buildAccessEventTooltip($wayStr, $accessPath, $accessWay, $action, $status, $delivery->inbound_channel);

            $events->push([
                'type' => 'access_event',
                'at' => $atIso,
                'summary' => $this->buildAccessEventSummary($wayStr, $accessPath, $action, $status, $delivery->inbound_channel),
                'detail_url' => route('admin.gestor-access-events.show', ['id' => $delivery->id]),
                'data' => [
                    'delivery_id' => $delivery->id,
                    'action' => $action,
                    'status' => $status,
                    'channel' => $delivery->inbound_channel,
                    'http_status' => $delivery->ieducar_frequencia_http_status,
                    'way' => $wayStr,
                    'access_path' => $accessPath,
                    'access_way' => is_string($accessWay) ? $accessWay : null,
                    'timeline_flow' => $timelineFlow,
                    'flow_label' => match ($timelineFlow) {
                        'entry' => 'Entrada',
                        'exit' => 'Saída / não-entrada',
                        default => 'Acesso',
                    },
                    'flow_caption' => match ($timelineFlow) {
                        'entry' => 'Fluxo de entrada — pode registar presença no iEducar.',
                        'exit' => 'Não envia ao iEducar — fica só no histórico GIDE.',
                        default => 'Evento de acesso sem classificação de sentido explícita.',
                    },
                    'tooltip' => $tooltip,
                ],
            ]);
        }
    }

    private function appendSmsEvents(Collection &$events, int $codAluno, int $limit): void
    {
        $smsItems = SmsDelivery::query()
            ->where('aluno_id', (string) $codAluno)
            ->latest()
            ->limit($limit)
            ->get();

        foreach ($smsItems as $sms) {
            $events->push([
                'type' => 'sms',
                'at' => ($sms->sent_at ?? $sms->created_at)?->toIso8601String() ?? '',
                'summary' => "SMS ({$sms->template_key}) — status: {$sms->status}",
                'detail_url' => route('sms.show', ['id' => $sms->id]),
                'data' => [
                    'sms_id' => $sms->id,
                    'template_key' => $sms->template_key,
                    'status' => $sms->status,
                    'to' => $sms->to,
                    'timeline_flow' => 'neutral',
                    'tooltip' => "SMS {$sms->template_key} — {$sms->status}",
                ],
            ]);
        }
    }

    private function appendFacialEvents(Collection &$events, int $codAluno, int $limit): void
    {
        $facialItems = FacialGestorCatracaHistory::query()
            ->where('aluno_id', (string) $codAluno)
            ->latest()
            ->limit($limit)
            ->get();

        foreach ($facialItems as $facial) {
            $events->push([
                'type' => 'facial',
                'at' => $facial->created_at?->toIso8601String() ?? '',
                'summary' => "Facial ({$facial->event_type}) — HTTP {$facial->http_status}",
                'detail_url' => $facial->facial_send_request_id
                    ? route('admin.facial-requests.show', ['id' => $facial->facial_send_request_id])
                    : null,
                'data' => [
                    'history_id' => $facial->id,
                    'event_type' => $facial->event_type,
                    'ok' => $facial->ok,
                    'http_status' => $facial->http_status,
                    'timeline_flow' => 'neutral',
                    'tooltip' => "Facial {$facial->event_type} — HTTP {$facial->http_status}",
                ],
            ]);
        }
    }

    private function extractCodAluno(GestorAccessEventDelivery $delivery): int
    {
        return $this->codAlunoFromDelivery($delivery);
    }

    private function codAlunoFromDelivery(GestorAccessEventDelivery $delivery): int
    {
        $analysis = is_array($delivery->analysis_json) ? $delivery->analysis_json : [];
        $payload = is_array($delivery->inbound_payload) ? $delivery->inbound_payload : [];

        foreach ([data_get($analysis, 'aluno_id'), data_get($payload, 'aluno_id')] as $raw) {
            if (is_numeric($raw) && (int) $raw > 0) {
                return (int) $raw;
            }
            if (is_string($raw) && preg_match('/(\d+)/', $raw, $m) && (int) $m[1] > 0) {
                return (int) $m[1];
            }
        }

        $fromName = data_get($payload, 'name');
        if (is_numeric($fromName) && (int) $fromName > 0) {
            return (int) $fromName;
        }
        if (is_string($fromName) && preg_match('/(\d+)/', $fromName, $m) && (int) $m[1] > 0) {
            return (int) $m[1];
        }

        return 0;
    }

    private function resolveTimelineFlow(string $action, mixed $accessPath): string
    {
        if ($action === 'mark_presence') {
            return 'entry';
        }
        if (in_array($accessPath, ['exit', 'non_entry'], true)) {
            return 'exit';
        }

        return 'neutral';
    }

    private function buildAccessEventTooltip(
        ?string $way,
        mixed $accessPath,
        mixed $accessWay,
        string $action,
        string $status,
        ?string $channel,
    ): string {
        $parts = [];
        if ($way !== null && $way !== '') {
            $parts[] = 'Sentido no equipamento (way): '.$way;
        }
        if (is_string($accessWay) && $accessWay !== '' && $accessWay !== $way) {
            $parts[] = 'Referência motor: '.$accessWay;
        }
        if ($accessPath === 'non_entry') {
            $parts[] = 'Não-entrada: registo apenas para histórico (sem POST ao iEducar).';
        } elseif ($accessPath === 'exit') {
            $parts[] = 'Saída / não-entrada: registo apenas para histórico (sem POST ao iEducar).';
        } elseif ($action === 'mark_presence') {
            $parts[] = 'Entrada reconhecida: fluxo pode enviar presença ao iEducar conforme configuração.';
        }
        $parts[] = 'Motor: '.$action.' · Estado: '.$status;
        if ($channel) {
            $parts[] = 'Canal: '.$channel;
        }

        return implode(' ', $parts);
    }

    private function buildAccessEventSummary(
        ?string $way,
        mixed $accessPath,
        string $action,
        string $status,
        ?string $channel,
    ): string {
        $bits = [];
        if ($way !== null && $way !== '') {
            $bits[] = 'way '.$way;
        }
        if ($accessPath === 'non_entry') {
            $bits[] = 'não-entrada (histórico)';
        } elseif ($accessPath === 'exit') {
            $bits[] = 'saída / não-entrada (histórico)';
        }
        $bits[] = 'motor '.$action;
        $bits[] = 'estado '.$status;
        if ($channel === GestorAccessEventDelivery::CHANNEL_CATRACA_BEARER) {
            $bits[] = 'catraca';
        }

        return 'Acesso — '.implode(' · ', $bits);
    }
}
