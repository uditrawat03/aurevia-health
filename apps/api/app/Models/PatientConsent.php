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
    'data_category',
    'purpose',
    'recipient_class',
    'status',
    'granted_by_user_id',
    'effective_from',
    'effective_until',
    'revoked_at',
    'revoked_by_user_id',
    'revocation_reason',
])]
final class PatientConsent extends Model
{
    use HasUlids;

    protected function casts(): array
    {
        return [
            'effective_from' => 'immutable_datetime',
            'effective_until' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
        ];
    }
}
