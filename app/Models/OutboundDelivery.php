<?php

namespace App\Models;

use App\Support\OutboundDeliveryStatuses;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class OutboundDelivery extends Model
{
    protected $fillable = [
        'integration_key',
        'event_type',
        'event_id',
        'endpoint',
        'payload',
        'attempts',
        'last_http_status',
        'last_error',
        'delivered_at',
        'next_retry_at',
        'delivery_status',
        'last_attempt_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'attempts' => 'integer',
            'last_http_status' => 'integer',
            'delivered_at' => 'datetime',
            'next_retry_at' => 'datetime',
            'last_attempt_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (OutboundDelivery $m) {
            $m->refreshDeliveryStatus();
        });
    }

    public function refreshDeliveryStatus(): void
    {
        $max = max(1, (int) config('gide.deliveries.max_attempts', 3));

        if ($this->delivered_at) {
            $this->delivery_status = OutboundDeliveryStatuses::COMPLETED;

            return;
        }
        if ((int) $this->attempts >= $max) {
            $this->delivery_status = OutboundDeliveryStatuses::FAILED;

            return;
        }
        if ($this->next_retry_at && $this->next_retry_at->isFuture()) {
            $this->delivery_status = OutboundDeliveryStatuses::RETRY_SCHEDULED;

            return;
        }

        $this->delivery_status = OutboundDeliveryStatuses::PENDING;
    }

    /**
     * Elegível a nova tentativa (cron + re-despacho de job), excluindo concluídos e falhas definitivas.
     */
    public function scopeEligibleForRetryDispatch(Builder $q): Builder
    {
        $max = max(1, (int) config('gide.deliveries.max_attempts', 3));

        return $q->whereNull('delivered_at')
            ->where('attempts', '<', $max)
            ->where(function ($q2) {
                $q2->whereNotNull('next_retry_at')
                    ->where('next_retry_at', '<=', now());
            });
    }

    /**
     * Possível job perdido (nunca incrementou tentativas).
     */
    public function scopeStaleNeverStarted(Builder $q, int $staleMinutes): Builder
    {
        return $q->whereNull('delivered_at')
            ->where('attempts', 0)
            ->whereNull('next_retry_at')
            ->where('updated_at', '<', now()->subMinutes($staleMinutes));
    }
}
