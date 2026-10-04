<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'patient_id',
    'use',
    'line1',
    'line2',
    'city',
    'region',
    'postal_code',
    'country_code',
    'preferred',
])]
final class PatientAddress extends Model
{
    use HasUlids;

    protected function casts(): array
    {
        return ['preferred' => 'boolean'];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
