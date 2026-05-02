<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'username', 'email', 'password', 'is_admin', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function isActive(): bool
    {
        return (bool) ($this->is_active ?? true);
    }

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeAdministrators(Builder $query): Builder
    {
        return $query->where('is_admin', true);
    }

    /**
     * Conta com login permitido (`is_active` nulo ou verdadeiro).
     *
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeWithActiveAccount(Builder $query): Builder
    {
        return $query->where(function (Builder $q): void {
            $q->whereNull('is_active')->orWhere('is_active', true);
        });
    }

    public static function activeAdministratorsCount(): int
    {
        return (int) static::query()->administrators()->withActiveAccount()->count();
    }
}
