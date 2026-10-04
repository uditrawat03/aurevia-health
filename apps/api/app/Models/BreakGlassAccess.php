<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'organization_id',
    'patient_id',
    'facility_id',
    'actor_user_id',
    'purpose',
    'reason',
    'activated_at',
    'expires_at',
])]
final class BreakGlassAccess extends Model
{
    use HasUlids;

    protected function casts(): array
    {
        return [
            'activated_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
        ];
    }
}
