<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnrollmentIngest extends Model
{
    protected $fillable = [
        'source',
        'event_id',
        'payload',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'received_at' => 'datetime',
        ];
    }
}
