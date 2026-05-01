<?php

namespace App\Services\Integrations;

use App\Jobs\SendEnrollmentToAccessControl;
use App\Jobs\SendPresenceSms;
use App\Models\AccessEvent;
use App\Models\EnrollmentIngest;
use App\Models\OutboundDelivery;
use App\Models\SmsDelivery;

/**
 * Re-despacha jobs quando a entrega falhou com backoff (next_retry_at) ou quando
 * o job inicial nunca foi consumido (fila/worker parado — modo opcional).
 *
 * O envio facial (stream) não passa por aqui: permanece síncrono no controller web.
 */
class DeliveryRetryDispatcher
{
    public function maxAttempts(): int
    {
        return max(1, (int) config('gide.deliveries.max_attempts', 3));
    }

    public function staleMinutes(): int
    {
        return max(1, (int) config('gide.deliveries.stale_minutes', 15));
    }

    /**
     * @return array{outbound: int, sms: int}
     */
    public function dispatchAll(bool $recoverStale = false): array
    {
        return [
            'outbound' => $this->dispatchOutboundRetries($recoverStale),
            'sms' => $this->dispatchSmsRetries($recoverStale),
        ];
    }

    public function dispatchOutboundRetries(bool $recoverStale = false): int
    {
        $max = $this->maxAttempts();
        $staleM = $this->staleMinutes();
        $dispatched = 0;

        $query = OutboundDelivery::query()
            ->where('integration_key', 'gestor')
            ->where('event_type', 'enrollment_ingest')
            ->whereNull('delivered_at')
            ->where('attempts', '<', $max)
            ->where(function ($q) use ($recoverStale, $staleM) {
                $q->where(function ($q2) {
                    $q2->whereNotNull('next_retry_at')
                        ->where('next_retry_at', '<=', now());
                });
                if ($recoverStale) {
                    $q->orWhere(function ($q2) use ($staleM) {
                        $q2->where('attempts', 0)
                            ->whereNull('next_retry_at')
                            ->where('updated_at', '<', now()->subMinutes($staleM));
                    });
                }
            });

        foreach ($query->cursor() as $delivery) {
            $ingest = EnrollmentIngest::query()
                ->where('source', 'ieducar')
                ->where('event_id', $delivery->event_id)
                ->first();
            if (! $ingest || ! is_array($ingest->payload)) {
                continue;
            }

            SendEnrollmentToAccessControl::dispatch($delivery->event_id, $ingest->payload);
            $dispatched++;
        }

        return $dispatched;
    }

    public function dispatchSmsRetries(bool $recoverStale = false): int
    {
        $max = $this->maxAttempts();
        $staleM = $this->staleMinutes();
        $dispatched = 0;

        $query = SmsDelivery::query()
            ->whereNull('sent_at')
            ->where('attempts', '<', $max)
            ->where(function ($q) use ($recoverStale, $staleM) {
                $q->where(function ($q2) {
                    $q2->whereNotNull('next_retry_at')
                        ->where('next_retry_at', '<=', now());
                });
                if ($recoverStale) {
                    $q->orWhere(function ($q2) use ($staleM) {
                        $q2->where('attempts', 0)
                            ->whereNull('next_retry_at')
                            ->where('updated_at', '<', now()->subMinutes($staleM));
                    });
                }
            });

        foreach ($query->cursor() as $sms) {
            $event = AccessEvent::query()
                ->where('source', 'gestor')
                ->where('event_id', $sms->event_id)
                ->first();
            if (! $event || ! is_array($event->payload) || ! is_array($event->analysis)) {
                continue;
            }

            $occurred = $sms->occurred_at ?? $event->occurred_at;

            SendPresenceSms::dispatch(
                $sms->event_id,
                $event->payload,
                $event->analysis,
                $occurred?->toIso8601String(),
            );
            $dispatched++;
        }

        return $dispatched;
    }
}
