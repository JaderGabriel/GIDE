<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\GestorAccessEventDelivery;
use App\Models\IeducarFrequenciaRegistroDelivery;
use App\Models\OutboundDelivery;
use App\Models\SmsDelivery;
use App\Support\DateDisplay;
use App\Support\OutboundDeliveryStatuses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GideQueuesController extends Controller
{
    private const TIPOS = ['todos', 'jobs', 'failed', 'outbound', 'sms', 'eventos', 'frequencia'];

    private const ESTADOS = ['todos', 'pendente', 'concluido', 'falha'];

    private const LIMITES = [50, 100, 150, 200, 300];

    public function index(Request $request): View
    {
        $tipo = (string) $request->query('tipo', 'todos');
        if (! in_array($tipo, self::TIPOS, true)) {
            $tipo = 'todos';
        }
        $estado = (string) $request->query('estado', 'todos');
        if (! in_array($estado, self::ESTADOS, true)) {
            $estado = 'todos';
        }
        $q = trim((string) $request->query('q', ''));
        $limite = (int) $request->query('limite', 150);
        if (! in_array($limite, self::LIMITES, true)) {
            $limite = 150;
        }

        $perSource = min(200, max(40, $limite));
        $isAdmin = (bool) $request->user()->is_admin;
        $rows = $this->collectRows($perSource, $isAdmin);
        $rows = $this->filterRows($rows, $tipo, $estado, $q);
        usort($rows, static fn (array $a, array $b): int => ($b['sort_ts'] ?? 0) <=> ($a['sort_ts'] ?? 0));
        $totalFiltrado = count($rows);
        $rows = array_slice($rows, 0, $limite);

        return view('integrations.gide_queues', [
            'rows' => $rows,
            'filters' => [
                'tipo' => $tipo,
                'estado' => $estado,
                'q' => $q,
                'limite' => $limite,
            ],
            'totalFiltrado' => $totalFiltrado,
            'integrationsOverviewAdmin' => $isAdmin,
            'queueDriver' => (string) config('queue.default', 'sync'),
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function collectRows(int $perSource, bool $isAdmin): array
    {
        $out = [];

        try {
            foreach (DB::table('jobs')->orderByDesc('id')->limit($perSource)->get() as $row) {
                $ca = (int) ($row->created_at ?? 0);
                $out[] = [
                    'kind' => 'job',
                    'sort_ts' => $ca > 0 ? $ca : 0,
                    'when_display' => $ca > 0 ? DateDisplay::formatHumanFromUnix($ca, true) : '—',
                    'tipo_label' => 'Jobs (Laravel)',
                    'id' => (int) $row->id,
                    'ref' => (string) ($row->queue ?? ''),
                    'status' => 'na fila',
                    'detail' => $this->summarizeJobPayload($row->payload ?? null),
                    'estado_bucket' => 'pendente',
                    'url' => null,
                ];
            }
        } catch (\Throwable) {
        }

        try {
            foreach (DB::table('failed_jobs')->orderByDesc('id')->limit($perSource)->get() as $row) {
                $fd = DateDisplay::carbon($row->failed_at ?? null);
                $ts = $fd ? $fd->getTimestamp() : 0;
                $out[] = [
                    'kind' => 'failed',
                    'sort_ts' => $ts,
                    'when_display' => $fd ? DateDisplay::formatHuman($fd, true) : '—',
                    'tipo_label' => 'Falhas (failed_jobs)',
                    'id' => (int) $row->id,
                    'ref' => (string) ($row->queue ?? ''),
                    'status' => 'falhou',
                    'detail' => $this->summarizeJobPayload($row->payload ?? null).' · '.mb_substr((string) ($row->exception ?? ''), 0, 120),
                    'estado_bucket' => 'falha',
                    'url' => null,
                ];
            }
        } catch (\Throwable) {
        }

        try {
            foreach (OutboundDelivery::query()->orderByDesc('updated_at')->limit($perSource)->get() as $d) {
                $st = (string) ($d->delivery_status ?? '');
                $bucket = $this->outboundEstadoBucket($st, $d->delivered_at !== null);
                $ts = $d->updated_at ? $d->updated_at->getTimestamp() : 0;
                $out[] = [
                    'kind' => 'outbound',
                    'sort_ts' => $ts,
                    'when_display' => $d->updated_at ? DateDisplay::formatHuman($d->updated_at, true) : '—',
                    'tipo_label' => 'Outbound (Gestor)',
                    'id' => $d->id,
                    'ref' => (string) $d->event_id,
                    'status' => $st !== '' ? $st : '—',
                    'detail' => $d->last_error ? mb_substr((string) $d->last_error, 0, 200) : null,
                    'estado_bucket' => $bucket,
                    'url' => null,
                ];
            }
        } catch (\Throwable) {
        }

        try {
            foreach (SmsDelivery::query()->orderByDesc('updated_at')->limit($perSource)->get() as $d) {
                $st = (string) ($d->status ?? '');
                $bucket = $this->smsEstadoBucket($st, $d->sent_at !== null);
                $ts = $d->updated_at ? $d->updated_at->getTimestamp() : 0;
                $out[] = [
                    'kind' => 'sms',
                    'sort_ts' => $ts,
                    'when_display' => $d->updated_at ? DateDisplay::formatHuman($d->updated_at, true) : '—',
                    'tipo_label' => 'SMS',
                    'id' => $d->id,
                    'ref' => (string) $d->event_id,
                    'status' => $st !== '' ? $st : '—',
                    'detail' => $d->last_error ? mb_substr((string) $d->last_error, 0, 200) : null,
                    'estado_bucket' => $bucket,
                    'url' => null,
                ];
            }
        } catch (\Throwable) {
        }

        try {
            foreach (GestorAccessEventDelivery::query()->orderByDesc('id')->limit($perSource)->get() as $d) {
                $st = (string) ($d->processing_status ?? '');
                $bucket = $this->gestorEventEstadoBucket($st);
                $ts = $d->processed_at?->getTimestamp()
                    ?? $d->updated_at?->getTimestamp()
                    ?? (int) $d->id;
                $when = $d->processed_at ?? $d->updated_at;
                $out[] = [
                    'kind' => 'gestor_event',
                    'sort_ts' => $ts,
                    'when_display' => $when ? DateDisplay::formatHuman($when, true) : '—',
                    'tipo_label' => 'Eventos (access)',
                    'id' => $d->id,
                    'ref' => (string) $d->event_id,
                    'status' => $st !== '' ? $st : '—',
                    'detail' => $d->ieducar_frequencia_error ? mb_substr((string) $d->ieducar_frequencia_error, 0, 200) : ((string) ($d->inbound_channel ?? '')),
                    'estado_bucket' => $bucket,
                    'url' => $isAdmin ? route('admin.gestor-access-events.show', ['id' => $d->id]) : null,
                ];
            }
        } catch (\Throwable) {
        }

        try {
            foreach (IeducarFrequenciaRegistroDelivery::query()->orderByDesc('id')->limit($perSource)->get() as $d) {
                $st = (string) ($d->status ?? '');
                $bucket = $this->frequenciaEstadoBucket($st);
                $ts = $d->sent_at?->getTimestamp()
                    ?? $d->updated_at?->getTimestamp()
                    ?? (int) $d->id;
                $when = $d->sent_at ?? $d->updated_at;
                $mode = (string) ($d->mode ?? '');
                $out[] = [
                    'kind' => 'frequencia_registro',
                    'sort_ts' => $ts,
                    'when_display' => $when ? DateDisplay::formatHuman($when, true) : '—',
                    'tipo_label' => 'Frequência (registro)',
                    'id' => $d->id,
                    'ref' => $mode !== '' ? $mode : '—',
                    'status' => $st !== '' ? $st : '—',
                    'detail' => $d->error_message ? mb_substr((string) $d->error_message, 0, 200) : null,
                    'estado_bucket' => $bucket,
                    'url' => $isAdmin ? route('admin.ieducar-frequencia-deliveries.show', ['id' => $d->id]) : null,
                ];
            }
        } catch (\Throwable) {
        }

        return $out;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function filterRows(array $rows, string $tipo, string $estado, string $q): array
    {
        $qNorm = mb_strtolower($q);

        return array_values(array_filter($rows, function (array $r) use ($tipo, $estado, $qNorm): bool {
            if ($tipo !== 'todos') {
                $map = [
                    'jobs' => 'job',
                    'failed' => 'failed',
                    'outbound' => 'outbound',
                    'sms' => 'sms',
                    'eventos' => 'gestor_event',
                    'frequencia' => 'frequencia_registro',
                ];
                if (($map[$tipo] ?? '') !== ($r['kind'] ?? '')) {
                    return false;
                }
            }
            if ($estado !== 'todos' && ($r['estado_bucket'] ?? '') !== $estado) {
                return false;
            }
            if ($qNorm !== '') {
                $hay = mb_strtolower(implode(' ', array_filter([
                    (string) ($r['tipo_label'] ?? ''),
                    (string) ($r['ref'] ?? ''),
                    (string) ($r['status'] ?? ''),
                    (string) ($r['detail'] ?? ''),
                    (string) ($r['id'] ?? ''),
                ])));

                return str_contains($hay, $qNorm);
            }

            return true;
        }));
    }

    private function outboundEstadoBucket(string $status, bool $delivered): string
    {
        if ($delivered || $status === OutboundDeliveryStatuses::COMPLETED) {
            return 'concluido';
        }
        if ($status === OutboundDeliveryStatuses::FAILED) {
            return 'falha';
        }

        return 'pendente';
    }

    private function smsEstadoBucket(string $status, bool $sent): string
    {
        if ($sent || $status === 'sent') {
            return 'concluido';
        }
        if ($status === 'error') {
            return 'falha';
        }

        return 'pendente';
    }

    private function gestorEventEstadoBucket(string $status): string
    {
        return match ($status) {
            GestorAccessEventDelivery::STATUS_COMPLETED => 'concluido',
            GestorAccessEventDelivery::STATUS_FAILED => 'falha',
            GestorAccessEventDelivery::STATUS_SKIPPED => 'concluido',
            default => 'pendente',
        };
    }

    private function frequenciaEstadoBucket(string $status): string
    {
        return match ($status) {
            IeducarFrequenciaRegistroDelivery::STATUS_COMPLETED => 'concluido',
            IeducarFrequenciaRegistroDelivery::STATUS_FAILED => 'falha',
            default => 'pendente',
        };
    }

    private function summarizeJobPayload(mixed $payload): string
    {
        if (! is_string($payload) || $payload === '') {
            return '—';
        }
        $j = json_decode($payload, true);
        if (! is_array($j)) {
            return mb_substr($payload, 0, 72);
        }
        $name = $j['displayName'] ?? data_get($j, 'data.commandName') ?? data_get($j, 'data.displayName') ?? 'job';
        $name = str_replace('\\\\', '\\', (string) $name);

        return class_basename($name);
    }
}
