<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'actor_user_id',
    'organization_id',
    'facility_id',
    'patient_id',
    'resource_type',
    'resource_id',
    'action',
    'outcome',
    'correlation_id',
    'occurred_at',
])]
final class AuditEvent extends Model
{
    use HasUlids;

    protected function casts(): array
    {
        return [
            'actor_user_id' => 'integer',
            'occurred_at' => 'immutable_datetime',
        ];
    }
}
