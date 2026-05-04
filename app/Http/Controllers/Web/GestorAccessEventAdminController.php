<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\GestorAccessEventDelivery;
use App\Support\AdminListPerPage;
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

        return view('admin.gestor_access_event_show', [
            'delivery' => $delivery,
        ]);
    }
}
