<?php

namespace App\Jobs;

use App\Services\Sms\SmsService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendPresenceSms implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $uniqueFor = 120;

    public function __construct(
        public readonly string $eventId,
        public readonly array $payload,
        public readonly array $analysis,
        public readonly ?string $occurredAtIso = null,
    ) {}

    public function handle(SmsService $service): void
    {
        $occurredAt = null;
        if ($this->occurredAtIso) {
            try {
                $occurredAt = Carbon::parse($this->occurredAtIso);
            } catch (\Throwable) {
                $occurredAt = null;
            }
        }

        $service->sendPresenceSms($this->eventId, $this->payload, $this->analysis, $occurredAt);
    }

    public function uniqueId(): string
    {
        return 'presence-sms:'.$this->eventId;
    }
}
