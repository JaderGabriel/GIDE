<?php

namespace App\Models;

use App\Http\Controllers\Api\CatracaAccessWebhookController;
use App\Http\Controllers\Api\GestorWebhookController;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Uma linha por POST em {@see GestorWebhookController}
 * ou {@see CatracaAccessWebhookController} (fila/auditoria).
 * Envio ao iEducar: {@see IeducarClient::postCatracaFrequenciaRegistro} com `meta.preview` alinhado ao setup do Gestor
 * (extra.ieducar_processing.environment) e à intenção do fluxo.
 */
class GestorAccessEventDelivery extends Model
{
    public const CHANNEL_GESTOR_HMAC = 'gestor_hmac';

    public const CHANNEL_CATRACA_BEARER = 'catraca_bearer';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_SKIPPED = 'skipped';

    protected $table = 'gestor_access_event_deliveries';

    protected $fillable = [
        'event_id',
        'inbound_channel',
        'access_event_id',
        'inbound_payload',
        'access_event_was_created',
        'processing_status',
        'gestor_ie_environment',
        'ieducar_preview_only',
        'analysis_json',
        'ieducar_marker_summary',
        'ieducar_frequencia_request_json',
        'ieducar_frequencia_http_status',
        'ieducar_frequencia_response_json',
        'ieducar_frequencia_error',
        'ieducar_attempts',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'inbound_payload' => 'array',
            'access_event_was_created' => 'boolean',
            'ieducar_preview_only' => 'boolean',
            'analysis_json' => 'array',
            'ieducar_marker_summary' => 'array',
            'ieducar_frequencia_request_json' => 'array',
            'ieducar_frequencia_http_status' => 'integer',
            'ieducar_frequencia_response_json' => 'array',
            'ieducar_attempts' => 'integer',
            'processed_at' => 'datetime',
        ];
    }

    public function accessEvent(): BelongsTo
    {
        return $this->belongsTo(AccessEvent::class, 'access_event_id');
    }
}
