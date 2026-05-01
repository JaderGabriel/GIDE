<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GideFacialInbound extends Model
{
    protected $fillable = [
        'operation',
        'cod_aluno',
        'idpes',
        'dedupe_key',
        'payload',
        'received_at',
        'processed_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'received_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }
}
