<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'organization_id',
    'registration_facility_id',
    'given_name',
    'normalized_given_name',
    'middle_name',
    'family_name',
    'normalized_family_name',
    'preferred_name',
    'date_of_birth',
    'sex_at_birth',
])]
final class Patient extends Model
{
    use HasUlids;

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function registrationFacility(): BelongsTo
    {
        return $this->belongsTo(Facility::class, 'registration_facility_id');
    }

    public function identifiers(): HasMany
    {
        return $this->hasMany(PatientIdentifier::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(PatientContact::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(PatientAddress::class);
    }

    public function relationships(): HasMany
    {
        return $this->hasMany(PatientRelationship::class);
    }
}
