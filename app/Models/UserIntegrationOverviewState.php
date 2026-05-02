<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserIntegrationOverviewState extends Model
{
    protected $table = 'user_integration_overview_states';

    protected $fillable = [
        'user_id',
        'lane_tests',
        'last_test',
        'last_test_key',
    ];

    protected function casts(): array
    {
        return [
            'lane_tests' => 'array',
            'last_test' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
