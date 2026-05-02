<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAuditLog extends Model
{
    /** Valor de `subject_type` quando o alvo da entrada é um utilizador. */
    public const SUBJECT_USER = 'user';

    protected $fillable = [
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'meta',
        'ip_address',
        'user_agent',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Autor OU alvo da gestão de conta (mesmo utilizador).
     *
     * @param  Builder<UserAuditLog>  $query
     * @return Builder<UserAuditLog>
     */
    public function scopeRelatedToUser(Builder $query, int $userId): Builder
    {
        return $query->where(function (Builder $q) use ($userId): void {
            $q->where('user_id', $userId)
                ->orWhere(function (Builder $q2) use ($userId): void {
                    $q2->where('subject_type', self::SUBJECT_USER)
                        ->where('subject_id', $userId);
                });
        });
    }
}
