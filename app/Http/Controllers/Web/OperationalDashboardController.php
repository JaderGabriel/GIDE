<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\FacialSendRequest;
use App\Models\GestorAccessEventDelivery;
use App\Models\IeducarFrequenciaRegistroDelivery;
use App\Models\OutboundDelivery;
use App\Models\SmsDelivery;
use App\Models\StudentEnrichmentCache;
use Illuminate\Support\Carbon;

class OperationalDashboardController extends Controller
{
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
        $dailyChart = $this->dailyAccessChart(14);
        $statusDistribution = $this->statusDistribution();
        $channelDistribution = $this->channelDistribution();

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
     * @return array<int, array{date: string, count: int}>
     */
    private function dailyAccessChart(int $days): array
    {
        $since = Carbon::now()->subDays($days)->startOfDay();

        $rows = GestorAccessEventDelivery::query()
            ->where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupByRaw('DATE(created_at)')
            ->orderBy('date')
            ->get();

        $result = [];
        for ($i = $days; $i >= 0; $i--) {
            $d = Carbon::now()->subDays($i)->format('Y-m-d');
            $found = $rows->firstWhere('date', $d);
            $result[] = ['date' => $d, 'count' => $found ? (int) $found->count : 0];
        }

        return $result;
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
