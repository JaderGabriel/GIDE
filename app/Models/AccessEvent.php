<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Evento recebido de catraca / Gestor (`source`: gestor, catraca_bearer, …).
 *
 * `analysis` (JSON): resultado do motor de presença; inclui `gestor_ieducar_environment` (`preview`|`homolog`),
 * `ieducar_outbound_channel` = catraca_frequencia_registro (preview), `marker` (resumo do POST preview), etc.
 */
class AccessEvent extends Model
{
    protected $fillable = [
        'source',
        'event_id',
        'payload',
        'occurred_at',
        'processed_at',
        'analysis',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'analysis' => 'array',
            'occurred_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }
}
