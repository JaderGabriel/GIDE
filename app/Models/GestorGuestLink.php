<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GestorGuestLink extends Model
{
    protected $fillable = [
        'cod_aluno',
        'invite_id',
        'guest_id',
        'last_invite_http_status',
        'last_invite_response_json',
        'last_face_http_status',
        'last_face_response_body',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'invite_id' => 'integer',
            'guest_id' => 'integer',
            'last_invite_http_status' => 'integer',
            'last_invite_response_json' => 'array',
            'last_face_http_status' => 'integer',
        ];
    }
}
