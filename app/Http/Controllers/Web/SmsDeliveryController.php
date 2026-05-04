<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SmsDelivery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SmsDeliveryController extends Controller
{
    public function index(Request $request)
    {
        $layout = (string) $request->query('layout', 'flat');

        $q = SmsDelivery::query();
        $this->applySmsFilters($q, $request);

        $filters = $this->smsFiltersFromRequest($request);

        if ($layout === 'grouped') {
            $rows = (clone $q)->orderByDesc('occurred_at')->orderByDesc('id')->limit(250)->get();
            $groupedTimeline = $this->buildSmsGroupedTimeline($rows);

            return view('sms.index', [
                'layout' => 'grouped',
                'groupedTimeline' => $groupedTimeline,
                'deliveries' => null,
                'filters' => $filters,
            ]);
        }

        $deliveries = (clone $q)->orderByDesc('id')->paginate(30)->withQueryString();

        return view('sms.index', [
            'layout' => 'flat',
            'groupedTimeline' => [],
            'deliveries' => $deliveries,
            'filters' => $filters,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function smsFiltersFromRequest(Request $request): array
    {
        return [
            'status' => (string) $request->query('status', ''),
            'to' => (string) $request->query('to', ''),
            'aluno_id' => trim((string) $request->query('aluno_id', '')),
            'matricula_id' => trim((string) $request->query('matricula_id', '')),
            'event_id' => trim((string) $request->query('event_id', '')),
            'from_date' => (string) $request->query('from_date', ''),
            'to_date' => (string) $request->query('to_date', ''),
            'layout' => (string) $request->query('layout', 'flat'),
        ];
    }

    private function applySmsFilters(Builder $q, Request $request): void
    {
        $status = (string) $request->query('status', '');
        if ($status !== '') {
            $q->where('status', $status);
        }

        $to = preg_replace('/\D+/', '', (string) $request->query('to', '')) ?? '';
        if ($to !== '') {
            $q->where('to', 'like', '%'.$to.'%');
        }

        $alunoId = trim((string) $request->query('aluno_id', ''));
        if ($alunoId !== '') {
            $q->where('aluno_id', $alunoId);
        }

        $matriculaId = trim((string) $request->query('matricula_id', ''));
        if ($matriculaId !== '') {
            $q->where('matricula_id', $matriculaId);
        }

        $eventId = trim((string) $request->query('event_id', ''));
        if ($eventId !== '') {
            $q->where('event_id', $eventId);
        }

        $fromDate = (string) $request->query('from_date', '');
        if ($fromDate !== '') {
            $q->whereDate('created_at', '>=', $fromDate);
        }

        $toDate = (string) $request->query('to_date', '');
        if ($toDate !== '') {
            $q->whereDate('created_at', '<=', $toDate);
        }
    }

    /**
     * @param  Collection<int, SmsDelivery>  $rows
     * @return list<array{aluno_id: string, last_at: int, occurrences: list<array{occurred_at: mixed, dispatches: Collection<int, SmsDelivery>}>}>
     */
    private function buildSmsGroupedTimeline(Collection $rows): array
    {
        $byAluno = $rows->groupBy(function (SmsDelivery $d) {
            $aid = $d->aluno_id;

            return ($aid !== null && $aid !== '') ? (string) $aid : '—';
        });

        $groups = [];
        foreach ($byAluno as $alunoKey => $items) {
            $byOcc = $items->groupBy(function (SmsDelivery $d) {
                return $d->occurred_at
                    ? $d->occurred_at->format('Y-m-d H:i:s')
                    : 'sem_data';
            });

            $occurrences = [];
            foreach ($byOcc->sortKeysDesc() as $timeKey => $dispatches) {
                $occurrences[] = [
                    'occurred_at' => $timeKey === 'sem_data' ? null : $dispatches->first()->occurred_at,
                    'dispatches' => $dispatches->sortBy('id')->values(),
                ];
            }

            $lastAt = (int) $items->max(function (SmsDelivery $d) {
                return $d->occurred_at?->getTimestamp() ?? $d->created_at?->getTimestamp() ?? 0;
            });

            $groups[] = [
                'aluno_id' => (string) $alunoKey,
                'last_at' => $lastAt,
                'occurrences' => $occurrences,
            ];
        }

        usort($groups, fn (array $a, array $b): int => ($b['last_at'] <=> $a['last_at']));

        return $groups;
    }

    public function show(int $id)
    {
        $delivery = SmsDelivery::query()->findOrFail($id);

        return view('sms.show', [
            'delivery' => $delivery,
        ]);
    }
}
