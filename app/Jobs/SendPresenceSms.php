<?php

namespace App\Jobs;

use App\Services\Sms\SmsService;
use App\Support\SmsTemplateKey;
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

    /**
     * @param  array<string, mixed>  $extraContext  Tags extra para o renderer (ex.: ieducar_http_status).
     */
    public function __construct(
        public readonly string $eventId,
        public readonly array $payload,
        public readonly array $analysis,
        public readonly ?string $occurredAtIso = null,
        public readonly string $templateKey = SmsTemplateKey::PRESENCE_CATRACA,
        public readonly array $extraContext = [],
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

        $service->sendPresenceSms($this->eventId, $this->payload, $this->analysis, $occurredAt, $this->templateKey, $this->extraContext);
    }

    public function uniqueId(): string
    {
        return 'presence-sms:'.$this->eventId.':'.$this->templateKey;
    }
}
