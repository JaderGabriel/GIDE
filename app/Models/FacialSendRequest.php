<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacialSendRequest extends Model
{
    protected $fillable = [
        'event_id',
        'payload',
        'token',
        'expires_at',
        'used_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }
}
