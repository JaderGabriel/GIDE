<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Presence\GestorAccessEventWebhookService;
use Illuminate\Http\Request;

class GestorWebhookController extends Controller
{
    public function store(Request $request, GestorAccessEventWebhookService $service)
    {
        $eventId = (string) $request->attributes->get('event_id', '');
        if ($eventId === '') {
            return response()->json(['message' => 'Event id ausente.'], 400);
        }

        $result = $service->handle($request, $eventId);

        $payload = [
            'ok' => true,
            'created' => $result['created'],
            'processed' => $result['processed'],
            'delivery_id' => $result['delivery_id'],
        ];
        if (! empty($result['queued'])) {
            $payload['queued'] = true;
        }

        return response()->json($payload);
    }
}
