<?php

namespace App\Jobs;

use App\Services\Presence\GestorAccessEventWebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Executa o POST de preview catraca-frequência ao iEducar para uma linha em {@see \App\Models\GestorAccessEventDelivery}.
 */
class ProcessGestorAccessEventDeliveryJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public int $uniqueFor = 3600;

    public function __construct(
        public readonly int $deliveryId,
    ) {}

    public function uniqueId(): string
    {
        return 'gestor-access-event-delivery-'.$this->deliveryId;
    }

    public function handle(GestorAccessEventWebhookService $service): void
    {
        $service->processIeducarForDelivery($this->deliveryId);
    }
}
