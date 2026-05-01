<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Integration extends Model
{
    protected $fillable = [
        'key',
        'name',
        'base_url',
        'enabled',
        'auth_type',
        'auth_token',
        'hmac_secret',
        'signature_ttl_seconds',
        'extra',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'signature_ttl_seconds' => 'integer',
            // Campos sensíveis
            'auth_token' => 'encrypted',
            'hmac_secret' => 'encrypted',
            // `extra` pode conter credenciais (ex.: username/password do gestor)
            'extra' => 'encrypted:array',
        ];
    }
}
