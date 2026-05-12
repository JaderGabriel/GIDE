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
     * @return Collection<int, array{cod_aluno: int, nome: string|null, last_event_at: string, delivery_id: int}>
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

            $cache = StudentEnrichmentCache::query()
                ->where('cod_aluno', $codAluno)
                ->first();

            $results[] = [
                'cod_aluno' => $codAluno,
                'nome' => $cache?->data['nome'] ?? null,
                'last_event_at' => $delivery->created_at?->toIso8601String() ?? '',
                'delivery_id' => $delivery->id,
            ];

            if (count($results) >= $limit) {
                break;
            }
        }

        return collect($results);
    }

    private function appendAccessEvents(Collection &$events, int $codAluno, int $limit): void
    {
        $deliveries = GestorAccessEventDelivery::query()
            ->whereNotNull('analysis_json')
            ->latest()
            ->limit($limit * 3)
            ->get();

        foreach ($deliveries as $delivery) {
            if ($this->extractCodAluno($delivery) !== $codAluno) {
                continue;
            }

            $action = data_get($delivery->analysis_json, 'action', 'unknown');
            $status = $delivery->processing_status;

            $events->push([
                'type' => 'access_event',
                'at' => $delivery->created_at?->toIso8601String() ?? '',
                'summary' => "Evento de acesso — motor: {$action}, status: {$status}",
                'detail_url' => route('admin.gestor-access-events.show', ['id' => $delivery->id]),
                'data' => [
                    'delivery_id' => $delivery->id,
                    'action' => $action,
                    'status' => $status,
                    'channel' => $delivery->inbound_channel,
                    'http_status' => $delivery->ieducar_frequencia_http_status,
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
                ],
            ]);
        }
    }

    private function extractCodAluno(GestorAccessEventDelivery $delivery): int
    {
        $fromAnalysis = data_get($delivery->analysis_json, 'aluno_id');
        if (is_numeric($fromAnalysis) && (int) $fromAnalysis > 0) {
            return (int) $fromAnalysis;
        }

        $fromPayload = data_get($delivery->inbound_payload, 'aluno_id');
        if (is_numeric($fromPayload) && (int) $fromPayload > 0) {
            return (int) $fromPayload;
        }

        return 0;
    }
}
