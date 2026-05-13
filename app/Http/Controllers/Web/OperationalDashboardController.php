<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\FacialSendRequest;
use App\Models\GestorAccessEventDelivery;
use App\Models\IeducarFrequenciaRegistroDelivery;
use App\Models\OutboundDelivery;
use App\Models\SmsDelivery;
use App\Models\StudentEnrichmentCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class OperationalDashboardController extends Controller
{
    public function queueLive(): JsonResponse
    {
        $now = Carbon::now();
        $fiveMin = $now->copy()->subMinutes(5);

        $ae = GestorAccessEventDelivery::query();
        $accessPending = (clone $ae)->where('processing_status', 'pending')->count();
        $accessProcessing = (clone $ae)->where('processing_status', 'processing')->count();
        $accessFailed = (clone $ae)->where('processing_status', 'failed')->count();
        $accessLast5 = (clone $ae)->where('created_at', '>=', $fiveMin)->count();
        $accessCompletedLast5 = (clone $ae)->where('processing_status', 'completed')
            ->where('updated_at', '>=', $fiveMin)->count();

        $smsPending = SmsDelivery::query()->whereIn('status', ['pending', 'queued'])->count();
        $smsFailed = SmsDelivery::query()->where('status', 'failed')->count();
        $smsLast5 = SmsDelivery::query()->where('created_at', '>=', $fiveMin)->count();

        $outRetry = OutboundDelivery::query()->where('delivery_status', 'RETRY_SCHEDULED')->count();
        $outFailed = OutboundDelivery::query()->where('delivery_status', 'FAILED')->count();
        $outPending = OutboundDelivery::query()->whereNull('delivered_at')
            ->whereNotIn('delivery_status', ['FAILED'])->count();

        $freqFailed = IeducarFrequenciaRegistroDelivery::query()->where('status', 'failed')->count();
        $freqPending = IeducarFrequenciaRegistroDelivery::query()
            ->whereNotIn('status', ['completed', 'failed'])->count();

        $totalPending = $accessPending + $accessProcessing + $smsPending + $outPending + $freqPending;
        $totalFailed = $accessFailed + $smsFailed + $outFailed + $freqFailed;

        return response()->json([
            'timestamp' => $now->format('H:i:s'),
            'total_pending' => $totalPending,
            'total_failed' => $totalFailed,
            'throughput_5min' => $accessCompletedLast5,
            'ingress_5min' => $accessLast5 + $smsLast5,
            'queues' => [
                [
                    'name' => 'Eventos de acesso',
                    'key' => 'access',
                    'pending' => $accessPending + $accessProcessing,
                    'failed' => $accessFailed,
                    'ingress' => $accessLast5,
                    'processed' => $accessCompletedLast5,
                ],
                [
                    'name' => 'SMS',
                    'key' => 'sms',
                    'pending' => $smsPending,
                    'failed' => $smsFailed,
                    'ingress' => $smsLast5,
                    'processed' => 0,
                ],
                [
                    'name' => 'Outbound Gestor',
                    'key' => 'outbound',
                    'pending' => $outPending,
                    'failed' => $outFailed,
                    'ingress' => 0,
                    'processed' => 0,
                ],
                [
                    'name' => 'Frequência iEducar',
                    'key' => 'frequencia',
                    'pending' => $freqPending,
                    'failed' => $freqFailed,
                    'ingress' => 0,
                    'processed' => 0,
                ],
            ],
        ]);
    }

    public function index()
    {
        $now = Carbon::now();
        $today = $now->copy()->startOfDay();
        $last7 = $now->copy()->subDays(7)->startOfDay();
        $last30 = $now->copy()->subDays(30)->startOfDay();

        $accessEvents = $this->accessEventMetrics($today, $last7, $last30);
        $sms = $this->smsMetrics($today, $last7);
        $outbound = $this->outboundMetrics($today, $last7);
        $facial = $this->facialMetrics($today, $last7);
        $frequencia = $this->frequenciaMetrics($today, $last7);
        $enrichment = $this->enrichmentMetrics();
        $dailyChart = $this->dailyVolumeChart(14);
        $statusDistribution = $this->statusDistribution();
        $channelDistribution = $this->channelDistribution();
        $health = $this->healthScore($accessEvents, $sms, $outbound, $frequencia, $enrichment);

        return view('admin.operational_dashboard', compact(
            'accessEvents',
            'sms',
            'outbound',
            'facial',
            'frequencia',
            'enrichment',
            'dailyChart',
            'statusDistribution',
            'channelDistribution',
            'health',
        ));
    }

    private function accessEventMetrics(Carbon $today, Carbon $last7, Carbon $last30): array
    {
        $base = GestorAccessEventDelivery::query();

        return [
            'total' => (clone $base)->count(),
            'today' => (clone $base)->where('created_at', '>=', $today)->count(),
            'last_7d' => (clone $base)->where('created_at', '>=', $last7)->count(),
            'last_30d' => (clone $base)->where('created_at', '>=', $last30)->count(),
            'pending' => (clone $base)->where('processing_status', 'pending')->count(),
            'processing' => (clone $base)->where('processing_status', 'processing')->count(),
            'completed' => (clone $base)->where('processing_status', 'completed')->count(),
            'failed' => (clone $base)->where('processing_status', 'failed')->count(),
            'skipped' => (clone $base)->where('processing_status', 'skipped')->count(),
            'mark_presence_today' => (clone $base)
                ->where('created_at', '>=', $today)
                ->whereRaw("analysis_json::text LIKE '%\"action\":\"mark_presence\"%'")
                ->count(),
        ];
    }

    private function smsMetrics(Carbon $today, Carbon $last7): array
    {
        $base = SmsDelivery::query();

        return [
            'total' => (clone $base)->count(),
            'today' => (clone $base)->where('created_at', '>=', $today)->count(),
            'last_7d' => (clone $base)->where('created_at', '>=', $last7)->count(),
            'sent' => (clone $base)->where('status', 'sent')->count(),
            'failed' => (clone $base)->where('status', 'failed')->count(),
            'pending' => (clone $base)->whereIn('status', ['pending', 'queued'])->count(),
        ];
    }

    private function outboundMetrics(Carbon $today, Carbon $last7): array
    {
        $base = OutboundDelivery::query();

        return [
            'total' => (clone $base)->count(),
            'today' => (clone $base)->where('created_at', '>=', $today)->count(),
            'last_7d' => (clone $base)->where('created_at', '>=', $last7)->count(),
            'delivered' => (clone $base)->whereNotNull('delivered_at')->count(),
            'failed' => (clone $base)->where('delivery_status', 'FAILED')->count(),
            'retry_scheduled' => (clone $base)->where('delivery_status', 'RETRY_SCHEDULED')->count(),
        ];
    }

    private function facialMetrics(Carbon $today, Carbon $last7): array
    {
        $base = FacialSendRequest::query();

        return [
            'total' => (clone $base)->count(),
            'today' => (clone $base)->where('created_at', '>=', $today)->count(),
            'last_7d' => (clone $base)->where('created_at', '>=', $last7)->count(),
            'used' => (clone $base)->whereNotNull('used_at')->count(),
            'expired' => (clone $base)->whereNull('used_at')->where('expires_at', '<', now())->count(),
        ];
    }

    private function frequenciaMetrics(Carbon $today, Carbon $last7): array
    {
        $base = IeducarFrequenciaRegistroDelivery::query();

        return [
            'total' => (clone $base)->count(),
            'today' => (clone $base)->where('created_at', '>=', $today)->count(),
            'last_7d' => (clone $base)->where('created_at', '>=', $last7)->count(),
            'completed' => (clone $base)->where('status', 'completed')->count(),
            'failed' => (clone $base)->where('status', 'failed')->count(),
        ];
    }

    private function enrichmentMetrics(): array
    {
        return [
            'cached_students' => StudentEnrichmentCache::query()->count(),
            'fresh' => StudentEnrichmentCache::query()->fresh()->count(),
            'expired' => StudentEnrichmentCache::query()->where('expires_at', '<=', now())->count(),
        ];
    }

    /**
     * Contagens por dia: eventos de acesso (Gestor/catraca) e solicitações faciais criadas.
     *
     * @return array<int, array{date: string, access: int, facial: int}>
     */
    private function dailyVolumeChart(int $days): array
    {
        $days = max(1, $days);
        $since = Carbon::now()->subDays($days - 1)->startOfDay();

        $accessMap = $this->dailyCountsByAppDay(
            GestorAccessEventDelivery::query()->where('created_at', '>=', $since),
        );
        $facialMap = $this->dailyCountsByAppDay(
            FacialSendRequest::query()->where('created_at', '>=', $since),
        );

        $result = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = Carbon::now()->subDays($i)->format('Y-m-d');
            $result[] = [
                'date' => $d,
                'access' => $accessMap[$d] ?? 0,
                'facial' => $facialMap[$d] ?? 0,
            ];
        }

        return $result;
    }

    /**
     * Conta linhas por dia civil no fuso da aplicação (evita divergência DATE() no SQL vs. calendário local).
     *
     * @param  Builder<Model>  $query
     * @return array<string, int>
     */
    private function dailyCountsByAppDay(Builder $query): array
    {
        $tz = config('app.timezone', 'UTC');
        $map = [];

        foreach ((clone $query)->select(['created_at'])->orderBy('id')->cursor() as $row) {
            $ca = $row->getAttribute('created_at');
            if ($ca === null) {
                continue;
            }
            $day = $ca instanceof Carbon
                ? $ca->copy()->timezone($tz)->format('Y-m-d')
                : Carbon::parse($ca)->timezone($tz)->format('Y-m-d');
            $map[$day] = ($map[$day] ?? 0) + 1;
        }

        return $map;
    }

    /**
     * Composite health score (0–100) broken into subsystems.
     * Each subsystem scores 0–100; the global score is a weighted average.
     */
    private function healthScore(array $ae, array $sms, array $out, array $freq, array $enrich): array
    {
        $subsystems = [];

        $subsystems[] = $this->subsystemHealth(
            'Eventos de acesso',
            'access',
            failures: $ae['failed'],
            queue: $ae['pending'] + $ae['processing'],
            total: max($ae['total'], 1),
            queueThreshold: 50,
        );

        $subsystems[] = $this->subsystemHealth(
            'SMS',
            'sms',
            failures: $sms['failed'],
            queue: $sms['pending'],
            total: max($sms['total'], 1),
            queueThreshold: 30,
        );

        $subsystems[] = $this->subsystemHealth(
            'Outbound Gestor',
            'outbound',
            failures: $out['failed'],
            queue: $out['retry_scheduled'],
            total: max($out['total'], 1),
            queueThreshold: 20,
        );

        $subsystems[] = $this->subsystemHealth(
            'Frequência iEducar',
            'frequencia',
            failures: $freq['failed'],
            queue: 0,
            total: max($freq['total'], 1),
            queueThreshold: 10,
        );

        $expiredRatio = $enrich['cached_students'] > 0
            ? ($enrich['expired'] / $enrich['cached_students']) * 100
            : 0;
        $enrichScore = max(0, 100 - $expiredRatio);
        $subsystems[] = [
            'name' => 'Enriquecimento',
            'key' => 'enrichment',
            'score' => (int) round($enrichScore),
            'status' => $enrichScore >= 80 ? 'ok' : ($enrichScore >= 50 ? 'warn' : 'bad'),
            'detail' => $enrich['expired'].' expirados de '.$enrich['cached_students'],
        ];

        $weights = ['access' => 35, 'sms' => 20, 'outbound' => 20, 'frequencia' => 15, 'enrichment' => 10];
        $weightedSum = 0;
        $totalWeight = 0;
        foreach ($subsystems as $s) {
            $w = $weights[$s['key']] ?? 10;
            $weightedSum += $s['score'] * $w;
            $totalWeight += $w;
        }
        $global = $totalWeight > 0 ? (int) round($weightedSum / $totalWeight) : 0;
        $globalStatus = $global >= 85 ? 'ok' : ($global >= 60 ? 'warn' : 'bad');

        return [
            'global' => $global,
            'status' => $globalStatus,
            'subsystems' => $subsystems,
        ];
    }

    private function subsystemHealth(string $name, string $key, int $failures, int $queue, int $total, int $queueThreshold): array
    {
        $failRate = ($failures / $total) * 100;
        $queuePenalty = min(30, ($queue / max($queueThreshold, 1)) * 30);
        $failPenalty = min(70, $failRate * 2);
        $score = max(0, (int) round(100 - $failPenalty - $queuePenalty));
        $status = $score >= 85 ? 'ok' : ($score >= 60 ? 'warn' : 'bad');

        $details = [];
        if ($failures > 0) {
            $details[] = $failures.' falhas ('.round($failRate, 1).'%)';
        }
        if ($queue > 0) {
            $details[] = $queue.' na fila';
        }
        if (empty($details)) {
            $details[] = 'Saudável';
        }

        return [
            'name' => $name,
            'key' => $key,
            'score' => $score,
            'status' => $status,
            'detail' => implode(' · ', $details),
        ];
    }

    private function statusDistribution(): array
    {
        return GestorAccessEventDelivery::query()
            ->selectRaw('processing_status, COUNT(*) as total')
            ->groupBy('processing_status')
            ->pluck('total', 'processing_status')
            ->toArray();
    }

    private function channelDistribution(): array
    {
        return GestorAccessEventDelivery::query()
            ->selectRaw("COALESCE(inbound_channel, 'gestor_hmac') as channel, COUNT(*) as total")
            ->groupByRaw("COALESCE(inbound_channel, 'gestor_hmac')")
            ->pluck('total', 'channel')
            ->toArray();
    }
}
