<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'organization_id',
    'patient_id',
    'type',
    'system',
    'value',
    'normalized_value',
])]
final class PatientIdentifier extends Model
{
    use HasUlids;

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
