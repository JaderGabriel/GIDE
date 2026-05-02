<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\IeducarFrequenciaRegistroDelivery;
use App\Models\Integration;
use App\Support\AdminListPerPage;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IeducarFrequenciaRegistroAdminController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = AdminListPerPage::resolve($request);

        $items = IeducarFrequenciaRegistroDelivery::query()
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        $ieducar = Integration::query()->where('key', 'ieducar')->first();
        $ieducarReady = (bool) ($ieducar && $ieducar->base_url && ($ieducar->auth_token || data_get($ieducar->extra, 'catraca_frequencia.confirmacao_token')));

        $base = IeducarFrequenciaRegistroDelivery::query();
        $stats = [
            'total' => (int) (clone $base)->count(),
            'pending' => (int) (clone $base)->where('status', IeducarFrequenciaRegistroDelivery::STATUS_PENDING)->count(),
            'processing' => (int) (clone $base)->where('status', IeducarFrequenciaRegistroDelivery::STATUS_PROCESSING)->count(),
            'completed' => (int) (clone $base)->where('status', IeducarFrequenciaRegistroDelivery::STATUS_COMPLETED)->count(),
            'failed' => (int) (clone $base)->where('status', IeducarFrequenciaRegistroDelivery::STATUS_FAILED)->count(),
            'preview' => (int) (clone $base)->where('mode', IeducarFrequenciaRegistroDelivery::MODE_PREVIEW)->count(),
            'apply' => (int) (clone $base)->where('mode', IeducarFrequenciaRegistroDelivery::MODE_APPLY)->count(),
        ];

        return view('admin.ieducar_frequencia_deliveries', [
            'items' => $items,
            'perPage' => $perPage,
            'ieducarReady' => $ieducarReady,
            'stats' => $stats,
        ]);
    }

    public function show(int $id): View
    {
        $delivery = IeducarFrequenciaRegistroDelivery::query()->findOrFail($id);

        return view('admin.ieducar_frequencia_delivery_show', [
            'delivery' => $delivery,
        ]);
    }
}
