<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'patient_id',
    'type',
    'name',
    'phone',
    'email',
    'legal_guardian',
    'emergency_contact',
])]
final class PatientRelationship extends Model
{
    use HasUlids;

    protected function casts(): array
    {
        return [
            'legal_guardian' => 'boolean',
            'emergency_contact' => 'boolean',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
