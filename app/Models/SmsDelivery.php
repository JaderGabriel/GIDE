<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsDelivery extends Model
{
    protected $fillable = [
        'event_id',
        'template_key',
        'to',
        'from',
        'message',
        'provider',
        'provider_message_id',
        'status',
        'aluno_id',
        'matricula_id',
        'window',
        'event_type',
        'occurred_at',
        'context',
        'provider_response',
        'attempts',
        'last_http_status',
        'last_error',
        'sent_at',
        'next_retry_at',
    ];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'last_http_status' => 'integer',
            'sent_at' => 'datetime',
            'next_retry_at' => 'datetime',
            'occurred_at' => 'datetime',
            'context' => 'array',
            'provider_response' => 'array',
        ];
    }
}
