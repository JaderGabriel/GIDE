<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IeducarFrequenciaRegistroDelivery extends Model
{
    public const MODE_PREVIEW = 'preview';

    public const MODE_APPLY = 'apply';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected $table = 'ieducar_frequencia_registro_deliveries';

    protected $fillable = [
        'user_id',
        'mode',
        'status',
        'payload',
        'http_status',
        'response_json',
        'error_message',
        'attempts',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'response_json' => 'array',
            'attempts' => 'integer',
            'http_status' => 'integer',
            'sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
