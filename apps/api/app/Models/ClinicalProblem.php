<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'organization_id',
    'facility_id',
    'patient_id',
    'encounter_id',
    'code_system',
    'code',
    'display',
    'status',
    'onset_date',
    'resolved_at',
    'recorded_by_user_id',
    'recorded_at',
])]
final class ClinicalProblem extends Model
{
    use HasUlids;

    protected function casts(): array
    {
        return [
            'onset_date' => 'immutable_date',
            'resolved_at' => 'immutable_datetime',
            'recorded_at' => 'immutable_datetime',
        ];
    }
}
