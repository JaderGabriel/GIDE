<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
