<?php

namespace App\Jobs;

use App\Services\Outbound\AccessControlOutboundService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendEnrollmentToAccessControl implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $uniqueFor = 120;

    public function __construct(
        public readonly string $eventId,
        public readonly array $payload,
    ) {}

    public function handle(AccessControlOutboundService $service): void
    {
        $service->sendEnrollmentPayload($this->eventId, $this->payload);
    }

    public function uniqueId(): string
    {
        return 'enrollment-outbound:'.$this->eventId;
    }
}
