<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacialIeducarStatusSnapshot extends Model
{
    protected $fillable = [
        'facial_send_request_id',
        'cod_aluno',
        'idpes',
        'http_status',
        'response_json',
        'fetched_at',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'http_status' => 'integer',
            'response_json' => 'array',
            'fetched_at' => 'datetime',
        ];
    }
}
