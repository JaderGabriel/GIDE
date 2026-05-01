<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacialEnrollAttempt extends Model
{
    protected $fillable = [
        'facial_send_request_id',
        'external_id',
        'ok',
        'http_status',
        'response_body',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'ok' => 'boolean',
            'http_status' => 'integer',
        ];
    }
}
