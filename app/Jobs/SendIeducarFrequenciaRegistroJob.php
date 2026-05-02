<?php

namespace App\Jobs;

use App\Models\IeducarFrequenciaRegistroDelivery;
use App\Models\Integration;
use App\Services\Ieducar\IeducarClient;
use App\Support\Ieducar\GideFrequenciaRegistroPlanB;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendIeducarFrequenciaRegistroJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(
        public readonly int $deliveryId,
    ) {}

    public function handle(): void
    {
        $delivery = IeducarFrequenciaRegistroDelivery::query()->find($this->deliveryId);
        if (! $delivery) {
            return;
        }
        if (! in_array($delivery->mode, [
            IeducarFrequenciaRegistroDelivery::MODE_PREVIEW,
            IeducarFrequenciaRegistroDelivery::MODE_APPLY,
        ], true)) {
            return;
        }
        if ($delivery->status === IeducarFrequenciaRegistroDelivery::STATUS_COMPLETED) {
            return;
        }

        $delivery->update(['status' => IeducarFrequenciaRegistroDelivery::STATUS_PROCESSING]);

        $payloadBase = GideFrequenciaRegistroPlanB::refreshDataRefsWithRandomClock((array) $delivery->payload);
        $delivery->update(['payload' => $payloadBase]);

        Log::info('ieducar_frequencia_registro.job_start', [
            'delivery_id' => $delivery->id,
            'mode' => $delivery->mode,
        ]);

        $ieducar = Integration::query()->where('key', 'ieducar')->where('enabled', true)->first();
        if (! $ieducar) {
            $delivery->update([
                'status' => IeducarFrequenciaRegistroDelivery::STATUS_FAILED,
                'error_message' => 'Integração iEducar inexistente ou desabilitada.',
                'sent_at' => now(),
            ]);
            Log::warning('ieducar_frequencia_registro.job_aborted', [
                'delivery_id' => $delivery->id,
                'reason' => 'ieducar_missing_or_disabled',
            ]);

            return;
        }

        $payload = $payloadBase;
        $payload['meta'] = [
            'contract_version' => IeducarClient::CAT_FREQUENCIA_CONTRACT_VERSION,
            'preview' => $delivery->mode === IeducarFrequenciaRegistroDelivery::MODE_PREVIEW,
        ];

        try {
            $resp = (new IeducarClient($ieducar))->postCatracaFrequenciaRegistro($payload);
            $json = $resp->json();
            $delivery->increment('attempts');
            $delivery->update([
                'status' => $resp->successful() ? IeducarFrequenciaRegistroDelivery::STATUS_COMPLETED : IeducarFrequenciaRegistroDelivery::STATUS_FAILED,
                'http_status' => $resp->status(),
                'response_json' => is_array($json) ? $json : ['raw' => $resp->body()],
                'error_message' => $resp->successful() ? null : (is_string($resp->body()) ? mb_substr($resp->body(), 0, 8000) : null),
                'sent_at' => now(),
            ]);
            Log::info('ieducar_frequencia_registro.job_http', [
                'delivery_id' => $delivery->id,
                'mode' => $delivery->mode,
                'http_status' => $resp->status(),
                'delivery_status' => $resp->successful()
                    ? IeducarFrequenciaRegistroDelivery::STATUS_COMPLETED
                    : IeducarFrequenciaRegistroDelivery::STATUS_FAILED,
            ]);
        } catch (\Throwable $e) {
            Log::warning('ieducar_frequencia_registro.job_exception', [
                'delivery_id' => $delivery->id,
                'mode' => $delivery->mode,
                'message' => $e->getMessage(),
            ]);
            $delivery->increment('attempts');
            $delivery->update([
                'status' => IeducarFrequenciaRegistroDelivery::STATUS_FAILED,
                'error_message' => $e->getMessage(),
                'sent_at' => now(),
            ]);
        }
    }
}
