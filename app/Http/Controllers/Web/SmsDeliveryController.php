<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SmsDelivery;
use Illuminate\Http\Request;

class SmsDeliveryController extends Controller
{
    public function index(Request $request)
    {
        $q = SmsDelivery::query()->orderByDesc('id');

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

        $deliveries = $q->paginate(30)->withQueryString();

        return view('sms.index', [
            'deliveries' => $deliveries,
            'filters' => [
                'status' => $status,
                'to' => (string) $request->query('to', ''),
                'aluno_id' => $alunoId,
                'matricula_id' => $matriculaId,
                'event_id' => $eventId,
                'from_date' => $fromDate,
                'to_date' => $toDate,
            ],
        ]);
    }

    public function show(int $id)
    {
        $delivery = SmsDelivery::query()->findOrFail($id);

        return view('sms.show', [
            'delivery' => $delivery,
        ]);
    }
}
