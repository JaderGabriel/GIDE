<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessGestorAccessEventDeliveryJob;
use App\Models\GestorAccessEventDelivery;
use App\Models\Integration;
use App\Support\AdminListPerPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GestorAccessEventAdminController extends Controller
{
    public function index(Request $request)
    {
        $perPage = AdminListPerPage::resolve($request);

        $q = GestorAccessEventDelivery::query()->orderByDesc('id');

        $status = trim((string) $request->query('status', ''));
        if ($status !== '' && in_array($status, [
            GestorAccessEventDelivery::STATUS_PENDING,
            GestorAccessEventDelivery::STATUS_PROCESSING,
            GestorAccessEventDelivery::STATUS_COMPLETED,
            GestorAccessEventDelivery::STATUS_FAILED,
            GestorAccessEventDelivery::STATUS_SKIPPED,
        ], true)) {
            $q->where('processing_status', $status);
        }

        $items = $q->paginate($perPage)->withQueryString();

        return view('admin.gestor_access_events', [
            'items' => $items,
            'perPage' => $perPage,
            'statusFilter' => $status,
        ]);
    }

    public function show(int $id)
    {
        $delivery = GestorAccessEventDelivery::query()->with('accessEvent')->findOrFail($id);
        $ieducar = Integration::query()->where('key', 'ieducar')->first();

        return view('admin.gestor_access_event_show', [
            'delivery' => $delivery,
            'ieducar' => $ieducar,
            'ieducarEnabled' => (bool) ($ieducar?->enabled),
        ]);
    }

    public function retry(int $id): RedirectResponse
    {
        $delivery = GestorAccessEventDelivery::query()->findOrFail($id);

        if (! in_array($delivery->processing_status, [
            GestorAccessEventDelivery::STATUS_PENDING,
            GestorAccessEventDelivery::STATUS_FAILED,
            GestorAccessEventDelivery::STATUS_PROCESSING,
        ], true)) {
            return redirect()
                ->back()
                ->withErrors(['retry' => 'Só é possível reenfileirar entregas pendentes, em processamento ou com falha no iEducar.']);
        }

        if ($delivery->processing_status !== GestorAccessEventDelivery::STATUS_PENDING) {
            $delivery->update([
                'processing_status' => GestorAccessEventDelivery::STATUS_PENDING,
                'processed_at' => null,
            ]);
        }

        ProcessGestorAccessEventDeliveryJob::dispatch($delivery->id);

        return redirect()
            ->back()
            ->with('status', 'Reenvio ao iEducar enfileirado.');
    }
}
