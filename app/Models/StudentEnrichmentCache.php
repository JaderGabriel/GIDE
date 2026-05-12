<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class StudentEnrichmentCache extends Model
{
    protected $table = 'student_enrichment_cache';

    protected $fillable = [
        'cod_aluno',
        'data',
        'fetched_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'cod_aluno' => 'integer',
            'data' => 'array',
            'fetched_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function scopeFresh(Builder $query): Builder
    {
        return $query->where('expires_at', '>', now());
    }
}
